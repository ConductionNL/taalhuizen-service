<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\LanguageHouse;
use App\Entity\Person;
use App\Entity\Provider;
use App\Entity\Session;
use App\Entity\User;
use Conduction\CommonGroundBundle\Service\AuthenticationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use ZxcvbnPhp\Zxcvbn;

class UcService
{
    private BsService $bsService;
    private CCService $ccService;
    private CommonGroundService $commonGroundService;
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private RequestStack $requestStack;
    private CacheInterface $cache;

    /**
     * UcService constructor.
     *
     * @param RequestStack   $requestStack
     * @param CacheInterface $cache
     * @param LayerService   $layerService
     */
    public function __construct(
        RequestStack $requestStack,
        CacheInterface $cache,
        LayerService $layerService
    ) {
        $this->bsService = $layerService->bsService;
        $this->ccService = new CCService($layerService);
        $this->commonGroundService = $layerService->commonGroundService;
        $this->entityManager = $layerService->entityManager;
        $this->parameterBag = $layerService->parameterBag;
        $this->requestStack = $requestStack;
        $this->cache = $cache;
    }

    /**
     * Writes a temporary file in the component file system.
     *
     * @param string $contents The contents of the file to write
     * @param string $type     The type of file to write
     *
     * @return string The location of the written file
     */
    public function writeFile(string $contents, string $type): string
    {
        $stamp = microtime().getmypid();
        file_put_contents(dirname(__FILE__, 3).'/var/'.$type.'-'.$stamp, $contents);

        return dirname(__FILE__, 3).'/var/'.$type.'-'.$stamp;
    }

    /**
     * Removes (temporary) files from the filesystem.
     *
     * @param array $files An array of file paths of files to delete
     */
    public function removeFiles(array $files): void
    {
        foreach ($files as $filename) {
            unlink($filename);
        }
    }

    /**
     * Creates a RS512-signed JWT token for a provided payload.
     *
     * @param array $payload The payload to encode
     *
     * @return string The resulting JWT token
     */
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

    /**
     * Validates a JWT token with the public key stored in the component.
     *
     * @param string $jws The signed JWT token to validate
     *
     * @throws Exception Thrown when the JWT token could not be verified
     *
     * @return array The payload of a verified JWT token
     */
    public function validateJWTAndGetPayload(string $jws): array
    {
        $serializer = new CompactSerializer();
        $jwt = $serializer->unserialize($jws);

        $algorithmManager = new AlgorithmManager([new RS512()]);
        $pem = $this->writeFile(base64_decode($this->parameterBag->get('public_key')), 'pem');
        $public = JWKFactory::createFromKeyFile($pem);
        $this->removeFiles([$pem]);

        $jwsVerifier = new JWSVerifier($algorithmManager);
        if ($jwsVerifier->verifyWithKey($jwt, $public, 0)) {
            return json_decode($jwt->getPayload(), true);
        }

        throw new Exception('Token could not be verified');
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    public function assessPassword(string $password): bool
    {
        $zxcvbn = new Zxcvbn();

        return !($zxcvbn->passwordStrength($password)['score'] < 4);
    }

    /**
     * @param string $password
     *
     * @return int
     */
    public function getPasswordScore(string $password): int
    {
        $zxcvbn = new Zxcvbn();

        return !$zxcvbn->passwordStrength($password)['score'];
    }

    /**
     * Returns the environment of the user based upon the organization type.
     *
     * @param string|null $type The type of the organization
     *
     * @return string The user environment
     */
    public function userEnvironmentEnum(?string $type): string
    {
        if ($type == 'LanguageHouse') {
            $result = 'LANGUAGEHOUSE';
        } elseif ($type == 'Provider') {
            $result = 'PROVIDER';
        } else {
            $result = 'BISC';
        }

        return $result;
    }

    /**
     * Takes a raw user array and a raw contact array and makes a user object out of it.
     *
     * @param array $raw     The raw user array
     * @param array $contact The raw contact array
     *
     * @return User The resulting user object
     */
    public function createUserObject(array $raw, Person $person): User
    {
        $user = new User();
        if (isset($raw['organization'])) {
            $org = $this->commonGroundService->getResource($raw['organization']);
        }
        $user->setPerson($person);
        $user->setPassword('');
        $user->setUsername($raw['username']);
        isset($org) && $org['id'] ? $user->setOrganizationId($org['id']) : null;
        $user->setUserEnvironment($this->userEnvironmentEnum(isset($org) ? $org['type'] : null));
        isset($org) && $org['name'] ? $user->setOrganizationName($org['name']) : null;
        $this->entityManager->persist($user);
        $user->setId(Uuid::fromString($raw['id']));
        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * Fetches a user from the user component and returns the raw array.
     *
     * @param string $id The id of the user to fetch
     *
     * @return array The raw array of the result
     */
    public function getUserArray(string $id): array
    {
        return $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'users', 'id' => $id]);
    }

    /**
     * Fetches a user from the user component and returns it as a user object.
     *
     * @param string $id The id of the user to fetch
     *
     * @throws Exception
     *
     * @return User The user returned
     */
    public function getUser(string $id): User
    {
        $userArray = $this->getUserArray($id);
        $person = $this->ccService->getEavPerson($userArray['person']);

        return $this->createUserObject($userArray, $this->ccService->createPersonObject($person));
    }

    /**
     * Fetches all users, or all users that fit the query provided and returns them as an array.
     *
     * @param array|null $query The query the users returned should fit
     *
     * @return array The array of user arrays of found users
     */
    public function getUsers(?array $query = []): array
    {
        return $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], $query)['hydra:member'];
    }

    /**
     * Updates the contact of an employee based upon the user id.
     *
     * @param string        $id            The user array of the employee
     * @param array         $employeeArray The employee array of the employee to edit
     * @param Employee|null $employee      The employee object of the employee to edit
     *
     * @throws Exception
     *
     * @return array The resulting contact array for the updated employee
     */
    public function updateUserContactForEmployee(string $id, array $employeeArray, ?Employee $employee = null): array
    {
        $personId = explode('/', $this->getUserArray($id)['person']);
        $personId = end($personId);
        $person = $this->ccService->employeeToPerson($employeeArray);
        $result = $this->ccService->updatePerson($personId, $person);

        return $result;
    }

    /**
     * Finds a user in an array of users and returns its ID.
     *
     * @param array  $users The array of users to search through
     * @param string $email The email address of the user to find
     *
     * @return string|null The id of found user, null if user doesn't exist
     */
    private function findUser(array $users, string $email): ?string
    {
        foreach ($users as $user) {
            if ($user['username'] == $email) {
                return $user['id'];
            }
        }

        return null;
    }

    /**
     * Creates a user from the data provided, and stores it in the user component.
     *
     * @param array $user The array of parameters provided
     *
     * @return User The resulting user
     */
    public function createUser(User $user): User
    {
//        $contact = $this->ccService->createPerson(['givenName' => $user['givenName'], 'familyName' => $user['familyName'], 'additionalName' => $user['additionalName'] ?? '', 'emails' => [['name' => 'email 1', 'email' => $user['email']]]]);

        $contact = $this->ccService->createPerson($user->getPerson());
        $resource = [
            'username'     => $user->getUsername(),
            'password'     => $user->getPassword(),
            'locale'       => 'nl',
            'person'       => $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $contact['id']]),
            'organization' => null,
        ];

        $result = $this->commonGroundService->createResource($resource, ['component' => 'uc', 'type' => 'users']);

        return $this->createUserObject($result, $this->ccService->createPersonObject($contact));
    }

    /**
     * Updates a user in the user component with the data provided.
     *
     * @param string $id        The id of the user to update
     * @param array  $userArray The data provided to update the user
     *
     * @throws Exception
     *
     * @return User The resulting user
     */
    public function updateUser(string $id, array $userArray): User
    {
        $resource = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'users', 'id' => $id]);
        $resource['username'] = key_exists('username', $userArray) ? $userArray['username'] : null;
        $resource['password'] = key_exists('password', $userArray) ? $userArray['password'] : null;

        $contact = $this->ccService->getEavPerson($resource['person']);

        if (key_exists('email', $userArray)) {
            $contact = $this->ccService->updatePerson($contact['id'], ['emails' => [['name' => 'email', 'email' => $userArray['email']]]]);
        }

        foreach ($resource['userGroups'] as &$userGroup) {
            $userGroup = '/groups/'.$userGroup['id'];
        }
        $result = $this->commonGroundService->updateResource($resource, ['component' => 'uc', 'type' => 'users', 'id' => $id]);

        if (isset($resource['password'])) {
            $this->bsService->sendPasswordChangedEmail($result['username'], $contact);
        }

        return $this->createUserObject($result, $this->ccService->createPersonObject($contact));
    }

    /**
     * Deletes a user.
     *
     * @param string $id The id of the user to remove
     *
     * @return bool Whether or not the action has been successful
     * @throws Exception
     */
    public function deleteUser(string $id): bool
    {
        $resource = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'users', 'id' => $id]);
        if ($resource['person']) {
            $this->ccService->deletePerson($this->commonGroundService->getUuidFromUrl($resource['person']));
        }

        return $this->commonGroundService->deleteResource(null, ['component' => 'uc', 'type' => 'users', 'id' => $id]);
    }

    /**
     * Logs in a user with username and password.
     *
     * @param string $username The username of the user to login
     * @param string $password The password of the user to login
     *
     * @return string|Response A JWT token for the user that is logged in
     */
    public function login(string $username, string $password)
    {
        $user = [
            'username'  => $username,
            'password'  => $password,
        ];

        try {
            $resource = $this->commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'login']);
        } catch (RequestException $exception) {
            return new Response(
                json_encode([
                    'message' => 'Authentication failed!',
                    'path'    => '',
                    'data'    => ['Exception' => $exception->getMessage()],
                ]),
                Response::HTTP_FORBIDDEN,
                ['content-type' => 'application/json']
            );
        }

        $time = new DateTime();
        $expiry = new DateTime('+10 days');

//        $this->entityManager->persist($session);
//        $this->entityManager->flush();

        $jwtBody = [
            'userId'    => $resource['id'],
            'username'  => $username,
            //            'session'   => $session->getId(),
            'type'      => 'login',
            'iss'       => $this->parameterBag->get('app_url'),
            'ias'       => $time->getTimestamp(),
            'exp'       => $expiry->getTimestamp(),
        ];

        return $this->createJWTToken($jwtBody);
    }

    /**
     * Creates a password reset token for a user with provided email.
     *
     * @param string $email     The email of the user that needs a password reset
     * @param bool   $sendEmail Whether or not an email has to be send for this reset (for example, a new user gets the link otherwise, so then an email is not send to prevent double emails)
     *
     * @return string The reset token
     */
    public function createPasswordResetToken(string $email, bool $sendEmail = true): string
    {
        $time = new DateTime();
        $expiry = new DateTime('+4 hours');
        $users = $this->getUsers(['username' => str_replace('+', '%2b', $email)]);
        $userId = $this->findUser($users, $email);

        if (!$userId) {
            return '';
        }

        $jwtBody = [
            'userId' => $userId,
            'email'  => $email,
            'type'   => 'passwordReset',
            'iss'    => $this->parameterBag->get('app_url'),
            'ias'    => $time->getTimestamp(),
            'exp'    => $expiry->getTimestamp(),
        ];

        $token = $this->createJWTToken($jwtBody);

        if ($sendEmail) {
            $this->bsService->sendPasswordResetMail($email, $token);
        }

        return $token;
    }

    /**
     * Updates a user password if a token has been provided.
     *
     * @param string $email    The email address of the user to update
     * @param string $token    The password reset token for the user
     * @param string $password The new password for the user
     *
     * @throws Exception Thrown when the email address provided in the request does not match the email address provided in the token
     *
     * @return User|Response The resulting user object
     */
    public function updatePasswordWithToken(string $email, string $token, string $password)
    {
        $tokenEmail = $this->validateJWTAndGetPayload($token);
        if ($tokenEmail['email'] != $email) {
            return new Response(
                json_encode([
                    'message' => 'Provided username does not match username from token!',
                    'path'    => 'username',
                    'data'    => ['username' => $email],
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }
        $userId = $tokenEmail['userId'];

        return $this->updateUser($userId, ['password' => $password]);
    }

    /**
     * Logs out the user that has been logged in.
     *
     * @throws \Psr\Cache\InvalidArgumentException Errors if the token cannot be invalidated in the cache
     *
     * @return bool Whether or not the user has been logged out
     */
    public function logout(): bool
    {
        $token = substr($this->requestStack->getCurrentRequest()->headers->get('Authorization'), strlen('Bearer '));

        $authenticationService = new AuthenticationService($this->parameterBag);
        $session = $authenticationService->verifyJWTToken($token);

        return true;
    }

    /**
     * Takes an array of user group ids and validates if the groups exist in the user component.
     *
     * @param array $usergroupIds The user group ids to check
     *
     * @return array The ids of the valid groups in the provided set of arrays
     */
    public function validateUserGroups(array $usergroupIds)
    {
        $validGroups = [];
        //check if groups exist
        foreach ($usergroupIds as $userGroupId) {
            $userGroupId = explode('/', $userGroupId);
            if (is_array($userGroupId)) {
                $userGroupId = end($userGroupId);
            }

            $userGroupUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'groups', 'id' => $userGroupId]);
            if ($this->commonGroundService->isResource($userGroupUrl)) {
                array_push($validGroups, $userGroupId);
            }
        }
        $usergroupIds = $validGroups;

        return $usergroupIds;
    }

    /**
     * Creates a coordinator group for a language house.
     *
     * @param array      $languageHouse        The language house to create the user group for
     * @param array      $userGroups           The user groups that already exist for the language house
     * @param array|null $userGroupCoordinator The existing coordinator user group
     *
     * @return array The user groups that exist for the language house
     */
    public function createTaalhuisCoordinatorGroup(array $languageHouse, array $userGroups, array $userGroupCoordinator): array
    {
        $coordinator = [
            'organization' => $languageHouse['@id'],
            'name'         => 'TAALHUIS_COORDINATOR',
            'description'  => 'UserGroup coordinator of '.$languageHouse['name'],
        ];
        if ($userGroupCoordinator) {
            $userGroups[] = $this->commonGroundService->updateResource($coordinator, ['component' => 'uc', 'type' => 'groups', 'id' => $userGroupCoordinator['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($coordinator, ['component' => 'uc', 'type' => 'groups']);
        }

        return $userGroups;
    }

    /**
     * Creates a employee group for a language house.
     *
     * @param array      $languageHouse     The language house to create the user group for
     * @param array      $userGroups        The user groups that already exist for the language house
     * @param array|null $userGroupEmployee The existing employee user group
     *
     * @return array The user groups that exist for the language house
     */
    public function createTaalhuisEmployeeGroup(array $languageHouse, array $userGroups, array $userGroupEmployee): array
    {
        $employee = [
            'organization' => $languageHouse['@id'],
            'name'         => 'TAALHUIS_EMPLOYEE',
            'description'  => 'UserGroup employee of '.$languageHouse['name'],
        ];
        if ($userGroupEmployee) {
            $userGroups[] = $this->commonGroundService->updateResource($employee, ['component' => 'uc', 'type' => 'groups', 'id' => $userGroupEmployee['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($employee, ['component' => 'uc', 'type' => 'groups']);
        }

        return $userGroups;
    }

    /**
     * Finds user groups in an array of user groups by their name.
     *
     * @param array  $userGroups The user group array
     * @param string $name       The name to look for
     *
     * @return array The existing user group (if it exists)
     */
    private function findUserGroupsByName(array $userGroups, string $name): ?array
    {
        foreach ($userGroups as $userGroup) {
            if ($userGroup['name'] == $name) {
                return $userGroup;
            }
        }

        return [];
    }

    /**
     * Creates the user groups for a language house.
     *
     * @param array $languageHouse The language house the groups have to be created for
     * @param array $userGroups    The user groups that already exist for the language house
     *
     * @return array The user groups that exist for the language house
     */
    public function createTaalhuisUserGroups(array $languageHouse, array $userGroups): array
    {
        $existingUserGroups = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $languageHouse['@id']])['hydra:member'];

        $userGroupCoordinator = $this->findUserGroupsByName($existingUserGroups, 'TAALHUIS_COORDINATOR');
        $userGroupEmployee = $this->findUserGroupsByName($existingUserGroups, 'TAALHUIS_EMPLOYEE');

        $userGroups = $this->createTaalhuisCoordinatorGroup($languageHouse, $userGroups, $userGroupCoordinator);
        $userGroups = $this->createTaalhuisEmployeeGroup($languageHouse, $userGroups, $userGroupEmployee);

        return $userGroups;
    }

    /**
     * Creates a coordinator user group for a provider.
     *
     * @param array $provider             The provider to create the user group for
     * @param array $userGroups           The existing user groups of the provider
     * @param array $userGroupCoordinator The existing coordinator user group
     *
     * @return array The user groups that exist for the provider
     */
    public function createProviderCoordinatorUserGroup(array $provider, array $userGroups, array $userGroupCoordinator): array
    {
        $coordinator = [
            'organization' => $provider['@id'],
            'name'         => 'AANBIEDER_COORDINATOR',
            'description'  => 'UserGroup coordinator of '.$provider['name'],
        ];
        if ($userGroupCoordinator) {
            $userGroups[] = $this->commonGroundService->updateResource($coordinator, ['component' => 'uc', 'type' => 'groups', 'id' => $userGroupCoordinator['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($coordinator, ['component' => 'uc', 'type' => 'groups']);
        }

        return $userGroups;
    }

    /**
     * Creates a mentor user group for a provider.
     *
     * @param array $provider        The provider to create the user group for
     * @param array $userGroups      The existing user groups of the provider
     * @param array $userGroupMentor The existing mentor user group
     *
     * @return array The user groups that exist for the provider
     */
    public function createProviderMentorUserGroup(array $provider, array $userGroups, array $userGroupMentor): array
    {
        $mentor = [
            'organization' => $provider['@id'],
            'name'         => 'AANBIEDER_MENTOR',
            'description'  => 'UserGroup mentor of '.$provider['name'],
        ];
        if ($userGroupMentor) {
            $userGroups[] = $this->commonGroundService->updateResource($mentor, ['component' => 'uc', 'type' => 'groups', 'id' => $userGroupMentor['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($mentor, ['component' => 'uc', 'type' => 'groups']);
        }

        return $userGroups;
    }

    /**
     * Creates a volunteer user group for a provider.
     *
     * @param array $provider           The provider to create the user group for
     * @param array $userGroups         The existing user groups of the provider
     * @param array $userGroupVolunteer The existing volunteer user group
     *
     * @return array
     */
    public function createProviderVolunteerUserGroup(array $provider, array $userGroups, array $userGroupVolunteer): array
    {
        $volunteer = [
            'organization' => $provider['@id'],
            'name'         => 'AANBIEDER_VOLUNTEER',
            'description'  => 'UserGroup volunteer of '.$provider['name'],
        ];
        if ($userGroupVolunteer) {
            $userGroups[] = $this->commonGroundService->updateResource($volunteer, ['component' => 'uc', 'type' => 'groups', 'id' => $userGroupVolunteer['id']]);
        } else {
            $userGroups[] = $this->commonGroundService->saveResource($volunteer, ['component' => 'uc', 'type' => 'groups']);
        }

        return $userGroups;
    }

    /**
     * Creates the required user groups for a provider.
     *
     * @param array $provider   The provider to create the user groups for
     * @param array $userGroups The existing user groups of a provider
     *
     * @return array The now existing user groups of the provider
     */
    public function createProviderUserGroups(array $provider, array $userGroups): array
    {
        $existingUserGroups = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $provider['@id']])['hydra:member'];

        $userGroupCoordinator = $this->findUserGroupsByName($existingUserGroups, 'AANBIEDER_COORDINATOR');
        $userGroupMentor = $this->findUserGroupsByName($existingUserGroups, 'AANBIEDER_MENTOR');
        $userGroupVolunteer = $this->findUserGroupsByName($existingUserGroups, 'AANBIEDER_VOLUNTEER');

        $userGroups = $this->createProviderCoordinatorUserGroup($provider, $userGroups, $userGroupCoordinator);
        $userGroups = $this->createProviderMentorUserGroup($provider, $userGroups, $userGroupMentor);
        $userGroups = $this->createProviderVolunteerUserGroup($provider, $userGroups, $userGroupVolunteer);

        return $userGroups;
    }

    /**
     * Creates the user groups for an organization.
     *
     * @param array  $organization The organization to create user groups for
     * @param string $type         The type of organization
     *
     * @return array
     */
    public function createUserGroups(array $organization, string $type): array
    {
        $userGroups = [];
        if ($type == 'LanguageHouse') {
            $userGroups = $this->createTaalhuisUserGroups($organization, $userGroups);
        } else {
            $userGroups = $this->createProviderUserGroups($organization, $userGroups);
        }

        return $userGroups;
    }

    /**
     * Deletes the user groups for an organization.
     *
     * @param string $ccOrganizationId The id of the organization for which the user groups should be removed
     *
     * @return bool Whether or not the operations have passed
     */
    public function deleteUserGroups(string $ccOrganizationId): bool
    {
        $userGroups = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $ccOrganizationId])['hydra:member'];
        if ($userGroups > 0) {
            foreach ($userGroups as $userGroup) {
                $this->commonGroundService->deleteResource(null, ['component'=>'uc', 'type' => 'groups', 'id' => $userGroup['id']]);
            }
        }

        return true;
    }

    /**
     * Fetches the user roles for an organization and returns them as array.
     *
     * @param string $organizationId The id of the organization for which the user roles should be fetched
     *
     * @return array The user roles for this organization as raw array
     */
    public function getUserRoles(string $organizationId): array
    {
        $organizationUrl = $this->commonGroundService->cleanUrl(['component'=>'cc', 'type'=>'organizations', 'id'=>$organizationId]);

        return $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'groups'], ['organization'=>$organizationUrl])['hydra:member'];
    }

    /**
     * Creates a user role object from a raw array.
     *
     * @param array  $userRoleArray The raw array of the user role object
     * @param string $type          The type of organization
     *
     * @return LanguageHouse|Provider
     */
    public function createUserRoleObject(array $userRoleArray, string $type)
    {
        if ($type == 'Taalhuis') {
            $organization = new LanguageHouse();
        } else {
            $organization = new Provider();
        }

        $organization->setName($userRoleArray['name']);
        $this->entityManager->persist($organization);
        $organization->setId(Uuid::fromString($userRoleArray['id']));
        $this->entityManager->persist($organization);

        return $organization;
    }

    /**
     * Fetches the user roles for an organization and returns them as collection.
     *
     * @param string $organizationId The organization to fetch the user roles for
     * @param string $type           The type of organization
     *
     * @return ArrayCollection The collection of user roles for the organization
     */
    public function getUserRolesByOrganization(string $organizationId, string $type): ArrayCollection
    {
        $id = explode('/', $organizationId);
        $userRoles = new ArrayCollection();

        $results = $this->getUserRoles(end($id));

        foreach ($results as $result) {
            $userRoles->add($this->createUserRoleObject($result, $type));
        }

        return $userRoles;
    }
}
