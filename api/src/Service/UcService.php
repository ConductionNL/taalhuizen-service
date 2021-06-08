<?php


namespace App\Service;


use App\Entity\Employee;
use App\Entity\LanguageHouse;
use App\Entity\Provider;
use App\Entity\User;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UcService
{
    private BsService $bsService;
    private CCService $ccService;
    private CommonGroundService $commonGroundService;
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;

    public function __construct(BsService $bsService, CCService $ccService, CommonGroundService $commonGroundService, EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag){
        $this->bsService = $bsService;
        $this->ccService = $ccService;
        $this->commonGroundService = $commonGroundService;
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
    }

    public function getUser(string $id): User
    {
        $userArray = $this->getUserArray($id);
        return $this->createUserObject($userArray, $this->commonGroundService->getResource($userArray['person']));

    }

    public function getUserArray(string $id): array
    {
        return $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'users', 'id' => $id]);
    }

    public function createUserObject(array $raw, array $contact): User
    {
        $user = new User();

        $user->setEmail(
            key_exists('emails', $contact) &&
            count($contact['emails'])  > 0 &&
            key_exists('email', $contact['emails'][array_key_first($contact['emails'])]) ?
                $contact['emails'][array_key_first($contact['emails'])]['email'] : $raw['username']
        );
        $user->setPassword('');
        $user->setUsername($raw['username']);
        $this->entityManager->persist($user);
        $user->setId(Uuid::fromString($raw['id']));
        $this->entityManager->persist($user);

        return $user;
    }

    public function updateUserContactForEmployee(string $id, array $employeeArray, ?Employee $employee = null): array
    {
        $personId = explode('/', $this->getUserArray($id)['person']);
        $personId = end($personId);
        $person = $this->ccService->employeeToPerson($employeeArray, $employee);
        $result = $this->ccService->updatePerson($personId, $person);

        return $result;
    }

    public function writeFile(string $contents, string $type): string
    {
        $stamp = microtime() . getmypid();
        file_put_contents(dirname(__FILE__, 3).'/var/'.$type.'-'.$stamp, $contents);

        return dirname(__FILE__, 3).'/var/'.$type.'-'.$stamp;
    }

    public function removeFiles(array $files): void
    {
        foreach($files as $filename){
            unlink($filename);
        }
    }

    public function createJWTToken(array $payload): string
    {
        $algorithmManager = new AlgorithmManager([new RS512()]);
        $pem = $this->writeFile(base64_decode($this->parameterBag->get('private_key')), 'pem');
        $jwk = JWKFactory::createFromKeyFile($pem);
        $this->removeFiles([$pem]);

        $jwsBuilder = new JWSBuilder($algorithmManager);
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($jwk, ['alg' => 'RS512'])
            ->build();

        $serializer = new CompactSerializer();
        return $serializer->serialize($jws, 0);
    }

    public function validateJWTAndGetPayload (string $jws): array
    {
        $serializer = new CompactSerializer();
        $jwt = $serializer->unserialize($jws);

        $algorithmManager = new AlgorithmManager([new RS512()]);
        $pem = $this->writeFile(base64_decode($this->parameterBag->get('public_key')), 'pem');
        $public = JWKFactory::createFromKeyFile($pem);
        $this->removeFiles([$pem]);

        $jwsVerifier = new JWSVerifier($algorithmManager);
        if($jwsVerifier->verifyWithKey($jwt, $public, 0)){
            return json_decode($jwt->getPayload(),true);
        }
        throw new \Exception('Token could not be verified');
    }

    public function getUsers(): array
    {
        return $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'])['hydra:member'];
    }

    public function requestPasswordReset(string $email, bool $sendEmail = true): string
    {
        $time = new DateTime();
        $expiry = new DateTime('+4 hours');

        $users = $this->getUsers();

        $found = false;
        foreach($users as $user){
            if($user['username'] == $email){
                $found = true;
                $userId = $user['id'];
            }
        }
        if(!$found){
            return '';
        }

        $jwtBody = [
            'userId' => $userId,
            'email' => $email,
            'type' => 'passwordReset',
            'iss' => $this->parameterBag->get('app_url'),
            'ias' => $time->getTimestamp(),
            'exp' => $expiry->getTimestamp(),
        ];

        $token = $this->createJWTToken($jwtBody);

        if($sendEmail){
            $this->bsService->sendPasswordResetMail($email, $token);
        }

        return $token;
    }

    public function login(string $username, string $password): string
    {
        $user = [
            'username'  => $username,
            'password'  => $password,
        ];
        $resource = $this->commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'login']);

        $time = new DateTime();
        $expiry = new DateTime('+10 days');

        $jwtBody = [
            'userId' => $resource['id'],
            'type' => 'login',
            'iss' => $this->parameterBag->get('app_url'),
            'ias' => $time->getTimestamp(),
            'exp' => $expiry->getTimestamp(),
        ];

        return $this->createJWTToken($jwtBody);
    }

    public function updatePasswordWithToken(string $email, string $token, string $password): User
    {
        $tokenEmail = $this->validateJWTAndGetPayload($token);
        if($tokenEmail['email'] != $email){
            throw new AccessDeniedHttpException('Provided email does not match email from token');
        }
        $userId = $tokenEmail['userId'];

        return $this->updateUser($userId, ['password' => $password]);
    }

    public function createUser(array $userArray): User
    {
        $contact = $this->ccService->createPerson(['givenName' => $userArray['username'], 'emails' => [['name' => 'email 1', 'email' => $userArray['email']]]]);
        $resource = [
            'username' => key_exists('username', $userArray) ? $userArray['username'] : null,
            'password' => key_exists('password', $userArray) ? $userArray['password'] : null,
            'locale' => 'nl',
            'person' => $contact['@id'],
        ];

        if(!$resource['username'] || !$resource['password']){
            throw new BadRequestException('Cannot create a user without both a username and password');
        }
        $result = $this->commonGroundService->createResource($resource, ['component' => 'uc', 'type' => 'users']);
        $user = new User();

        return $this->createUserObject($result, $contact);
    }

    public function updateUser(string $id, array $userArray): User
    {
        $resource = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'users', 'id' => $id]);
        $resource['username'] = key_exists('username', $userArray) ? $userArray['username'] : null;
        $resource['password'] = key_exists('password', $userArray) ? $userArray['password'] : null;

        $contact = $this->commonGroundService->getResource($resource['person']);

        if(key_exists('email', $userArray)){
            $contact = $this->ccService->updatePerson($contact['id'], ['emails' => [['name' => 'email', 'email' => $userArray['email']]]]);
        }

        $result = $this->commonGroundService->updateResource($resource, ['component' => 'uc', 'type' => 'users', 'id' => $id]);

        if(isset($resource['password'])){
            $this->bsService->sendPasswordChangedEmail($result['username'], $contact);
        }

        return $this->createUserObject($result, $contact);
    }

    public function validateUserGroups(array $usergroupIds){
        $vaildGroups = [];
        //check if groups exist
        foreach ($usergroupIds as $userGroupId){
            $userGroupId = explode('/',$userGroupId);
            if (is_array($userGroupId)) $userGroupId = end($userGroupId);

            $userGroupUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'groups', 'id' => $userGroupId]);
            if ($this->commonGroundService->isResource($userGroupUrl)) array_push($vaildGroups,$userGroupId);
        }
        $usergroupIds = $vaildGroups;
        return $usergroupIds;
    }

    public function createTaalhuisCoordinatorGroup(array $result, array $userGroups, ?array $userGroupCoordinator = null): array
    {
        $coordinator = [
            'organization' => $result['@id'],
            'name' => 'TAALHUIS_COORDINATOR',
            'description' => 'UserGroup coordinator of '.$result['name'],
        ];
        if ($userGroupCoordinator) {
            $userGroups[] = $this->commonGroundService->updateResource($coordinator,['component' => 'uc', 'type' => 'groups', 'id' => $userGroupCoordinator['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($coordinator,['component' => 'uc', 'type' => 'groups']);
        }
        return $userGroups;
    }
    public function createTaalhuisEmployeeGroup (array $result, array $userGroups, ?array $userGroupEmployee = null): array
    {
        $employee = [
            'organization' => $result['@id'],
            'name' => 'TAALHUIS_EMPLOYEE',
            'description' => 'UserGroup employee of '.$result['name'],
        ];
        if ($userGroupEmployee) {
            $userGroups[] = $this->commonGroundService->updateResource($employee,['component' => 'uc', 'type' => 'groups', 'id' => $userGroupEmployee['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($employee,['component' => 'uc', 'type' => 'groups']);
        }
        return $userGroups;
    }
    public function createTaalhuizenUserGroups(array $result, array $userGroups, bool $update): array
    {
        if ($update){
            $userGroupCoordinator = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $result['@id']])['hydra:member'][0];
            $userGroupEmployee = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $result['@id']])['hydra:member'][1];
        } else {
            $userGroupCoordinator = null;
            $userGroupEmployee = null;
        }
        $userGroups = $this->createTaalhuisCoordinatorGroup($result, $userGroups, $userGroupCoordinator);
        $userGroups = $this->createTaalhuisEmployeeGroup($result, $userGroups, $userGroupEmployee);
        return $userGroups;
    }

    public function createProviderCoordinatorUserGroup(array $result, array $userGroups, ?array $userGroupCoordinator = null): array
    {
        $coordinator = [
            'organization' => $result['@id'],
            'name' => 'AANBIEDER_COORDINATOR',
            'description' => 'UserGroup coordinator of '.$result['name'],
        ];
        if ($userGroupCoordinator) {
            $userGroups[] = $this->commonGroundService->updateResource($coordinator,['component' => 'uc', 'type' => 'groups', 'id' => $userGroupCoordinator['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($coordinator,['component' => 'uc', 'type' => 'groups']);
        }
        return $userGroups;
    }

    public function createProviderMentorUserGroup(array $result, array $userGroups, ?array $userGroupMentor = null): array
    {
        $mentor = [
            'organization' => $result['@id'],
            'name' => 'AANBIEDER_MENTOR',
            'description' => 'UserGroup mentor of '.$result['name'],
        ];
        if ($userGroupMentor) {
            $userGroups[] = $this->commonGroundService->updateResource($mentor,['component' => 'uc', 'type' => 'groups', 'id' => $userGroupMentor['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($mentor,['component' => 'uc', 'type' => 'groups']);
        }
        return $userGroups;
    }

    public function createProviderVolunteerUserGroup(array $result, array $userGroups, ?array $userGroupVolunteer = null): array
    {
        $volunteer = [
            'organization' => $result['@id'],
            'name' => 'AANBIEDER_VOLUNTEER',
            'description' => 'UserGroup volunteer of '.$result['name'],
        ];
        if ($userGroupVolunteer) {
            $userGroups[] = $this->commonGroundService->updateResource($volunteer,['component' => 'uc', 'type' => 'groups', 'id' => $userGroupVolunteer['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($volunteer,['component' => 'uc', 'type' => 'groups']);
        }
        return $userGroups;
    }
    public function createProviderUserGroups(array $result, array $userGroups, bool $update): array
    {
        if ($update){
            $userGroupCoordinator = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $result['@id']])['hydra:member'][0];
            $userGroupMentor = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $result['@id']])['hydra:member'][1];
            $userGroupVolunteer = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $result['@id']])['hydra:member'][2];
        } else {
            $userGroupCoordinator = null;
            $userGroupMentor = null;
            $userGroupVolunteer = null;
        }
        $userGroups = $this->createProviderCoordinatorUserGroup($result, $userGroups, $userGroupCoordinator);
        $userGroups = $this->createProviderMentorUserGroup($result, $userGroups, $userGroupMentor);
        $userGroups = $this->createProviderVolunteerUserGroup($result, $userGroups, $userGroupVolunteer);
        return $userGroups;
    }
    public function createUserGroups(array $result, $type, $update = false): array
    {
        $userGroups = [];
        if ($type == 'Taalhuis') {
            $userGroups = $this->createTaalhuizenUserGroups($result, $userGroups, $update);
        } else {
            $userGroups = $this->createProviderUserGroups($result, $userGroups, $update);
        }
        return $userGroups;
    }

    public function deleteUser(string $id): bool
    {
        return $this->commonGroundService->deleteResource(null, ['component' => 'uc', 'type' => 'users', 'id' => $id]);
    }

    public function deleteUserGroups(string $ccOrganizationId): bool
    {
        $userGroups = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $ccOrganizationId])['hydra:member'];
        if ($userGroups > 0) {
            foreach ($userGroups as $userGroup) {
                $this->commonGroundService->deleteResource(null, ['component'=>'uc', 'type' => 'groups', 'id' => $userGroup['id']]);
            }
        }
        return false;
    }

    public function getUserRolesByOrganization($organizationId, $type): ArrayCollection
    {
        $id = explode('/', $organizationId);
        $userRoles = new ArrayCollection();

        $results = $this->getUserRoles(end($id));

        foreach ($results as $result) {
            $userRoles->add($this->createUserRoleObject($result, $type));
        }

        return $userRoles;
    }

    public function getUserRoles($id): array
    {
        $organizationUrl = $this->commonGroundService->cleanUrl(['component'=>'cc', 'type'=>'organizations', 'id'=>$id]);
        $userRolesByLanguageHouse =  $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'groups'], ['organization'=>$organizationUrl])['hydra:member'];

        return $userRolesByLanguageHouse;
    }

    public function createUserRoleObject(array $result, $type)
    {
        if ($type == 'Taalhuis') {
            $organization = new LanguageHouse();
        } else {
            $organization = new Provider();
        }

        $organization->setName($result['name']);
        $this->entityManager->persist($organization);
        $organization->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($organization);
        return $organization;
    }
}
