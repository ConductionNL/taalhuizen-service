<?php


namespace App\Service;


use App\Entity\Employee;
use App\Entity\User;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
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
        $org = $this->commonGroundService->getResource($raw['organization']);
        $user->setPassword('');
        $user->setUsername($raw['username']);
        $user->setGivenName($contact['givenName']);
        $user->setAdditionalName($contact['additionalName']);
        $user->setFamilyName($contact['familyName']);
//        $user->setOrganizationId($org['id']);
//        $user->setUserRoles();
//        $user->setOrganizationName($org['name']);
//        $user->setUserEnvironment();
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

    public function deleteUser(string $id): bool
    {
        return $this->commonGroundService->deleteResource(null, ['component' => 'uc', 'type' => 'users', 'id' => $id]);
    }

    public function validateUserGroups(array $usergroupIds): array
    {
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
}
