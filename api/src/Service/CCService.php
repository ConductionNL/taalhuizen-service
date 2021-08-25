<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Email;
use App\Entity\Employee;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\StudentPermission;
use App\Entity\Telephone;
use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use function GuzzleHttp\json_decode;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class CCService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private WRCService $wrcService;
    private SerializerInterface $serializer;

    /**
     * CCService constructor.
     *
     * @param LayerService $layerService
     */
    public function __construct(
        LayerService $layerService
    ) {
        $this->entityManager = $layerService->entityManager;
        $this->serializer = $layerService->serializer;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->eavService = new EAVService($layerService->commonGroundService);
        $this->wrcService = new WRCService($layerService->entityManager, $layerService->commonGroundService);
    }

    /**
     * Removes empty fields from an array recursively.
     *
     * @param array $array The array to clean
     *
     * @return array The cleaned array
     */
    public function cleanResource(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->cleanResource($value);
            } elseif (!is_bool($value) and !$value) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @param array $result
     *
     * @return Organization
     */
    public function createOrganizationObject(array $result): Organization
    {
        $organization = new Organization();
        $organization->setName($result['name']);
        $organization->setType($result['type'] ?? null);
        $organization->setAddresses(isset($result['addresses'][0]) ? $this->createAddressObject($result['addresses'][0]) : null);
        $organization->setEmails(isset($result['emails'][0]) ? $this->createEmailObject($result['emails'][0]) : null);
        $organization->setTelephones(isset($result['telephones'][0]) ? $this->createTelephoneObject($result['telephones'][0]) : null);

        $this->entityManager->persist($organization);
        $organization->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($organization);

        return $organization;
    }

    public function createStudentPermissionsObject(array $result): ?StudentPermission
    {
        if (
            isset($result['didSignPermissionForm']) &&
            isset($result['hasPermissionToShareDataWithAanbieders']) &&
            isset($result['hasPermissionToShareDataWithLibraries']) &&
            isset($result['hasPermissionToSendInformationAboutLibraries'])
        ) {
            $permissions = new StudentPermission();
            $permissions->setHasPermissionToShareDataWithProviders($result['hasPermissionToShareDataWithAanbieders']);
            $permissions->setHasPermissionToShareDataWithLibraries($result['hasPermissionToShareDataWithLibraries']);
            $permissions->setHasPermissionToSendInformationAboutLibraries($result['hasPermissionToSendInformationAboutLibraries']);
            $permissions->setDidSignPermissionForm($result['didSignPermissionForm']);

            return $permissions;
        } else {
            return null;
        }
    }

    /**
     * @param array $result
     *
     * @throws Exception
     *
     * @return Person
     */
    public function createPersonObject(array $result): Person
    {
        $person = new Person();
        $person->setGivenName($result['givenName']);
        $person->setAdditionalName($result['additionalName'] ?? null);
        $person->setFamilyName($result['familyName']);
        $person->setGender($result['gender'] ?? null);
        $person->setBirthday($result['birthday'] ? new \DateTime($result['birthday']) : null);
        if ($result['contactPreference'] != 'OTHER') {
            $person->setContactPreference($result['contactPreference']);
            $person->setContactPreferenceOther(null);
        } else {
            $person->setContactPreference('OTHER');
            $person->setContactPreferenceOther($result['contactPreferenceOther']);
        }
        $person->setAddresses(isset($result['addresses'][0]) ? $this->createAddressObject($result['addresses'][0]) : null);
        $person->setEmails(isset($result['emails'][0]) ? $this->createEmailObject($result['emails'][0]) : null);
        foreach ($result['telephones'] as $telephone) {
            $person->addTelephone(isset($telephone) ? $this->createTelephoneObject($telephone) : null);
        }
        $person->setOrganization(null);
        if (isset($result['organization']['id'])) {
            $person->setOrganization($this->getOrganization($result['organization']['id']));
        } // else use cc/person sourceOrganization instead?

        $this->entityManager->persist($person);
        $person->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($person);

        return $person;
    }

    /**
     * @param array $result
     *
     * @return Address
     */
    public function createAddressObject(array $result): Address
    {
        $address = new Address();
        $address->setName($result['name'] ?? null);
        $address->setStreet($result['street']);
        $address->setHouseNumber($result['houseNumber']);
        $address->setHouseNumberSuffix($result['houseNumberSuffix'] ?? null);
        $address->setPostalCode($result['postalCode']);
        $address->setLocality($result['locality']);

        $this->entityManager->persist($address);
        $address->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($address);

        return $address;
    }

    /**
     * @param array $result
     *
     * @return Email
     */
    public function createEmailObject(array $result): Email
    {
        $email = new Email();
        $email->setName($result['name'] ?? null);
        $email->setEmail($result['email']);

        $this->entityManager->persist($email);
        $email->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($email);

        return $email;
    }

    /**
     * @param array $result
     *
     * @return Telephone
     */
    public function createTelephoneObject(array $result): Telephone
    {
        $telephone = new Telephone();
        $telephone->setName($result['name'] ?? null);
        $telephone->setTelephone($result['telephone']);

        $this->entityManager->persist($telephone);
        $telephone->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($telephone);

        return $telephone;
    }

    /**
     * Fetches organizations from the contact catalogue and returns an collection of organizations.
     *
     * @param string|null $type The type of organization to fetch
     *
     * @return ArrayCollection a collection of all organizations of the provided type
     */
    public function getOrganizations(array $query = []): ArrayCollection
    {
        $organizations = new ArrayCollection();

        $results = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], $query);

        foreach ($results['hydra:member'] as $result) {
            $organizations->add($this->createOrganizationObject($result));
        }

        $result = [
            '@context'          => '/contexts/Organization',
            '@id'               => '/organizations',
            '@type'             => 'hydra:Collection',
            'hydra:member'      => $organizations,
            'hydra:totalItems'  => $results['hydra:totalItems'] ?? count($results),
        ];
        if (key_exists('hydra:view', $results)) {
            $result['hydra:view'] = $results['hydra:view'];
        }

        return new ArrayCollection($result);
    }

    /**
     * Fetches an organization from the contact catalogue and returns it as an object for the type of organization.
     *
     * @param string $id The id of the organization to fetch
     *
     * @return Organization The organization that has been fetched
     */
    public function getOrganization(string $id)
    {
        $result = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);

        return $this->createOrganizationObject($result);
    }

    /**
     * Creates an organization in the contact catalogue.
     *
     * @param array  $organizationArray The array of the organization to save
     * @param string $type              The type of organization to save
     *
     * @return array|false The resulting organization in the contact catalogue
     */
    public function createOrganization(array $organizationArray, string $type)
    {
        $wrcOrganization = $this->wrcService->createOrganization($organizationArray);
        $address = [
            'name'              => $organizationArray['addresses']['name'] ?? null,
            'street'            => $organizationArray['addresses']['street'] ?? null,
            'houseNumber'       => $organizationArray['addresses']['houseNumber'] ?? null,
            'houseNumberSuffix' => $organizationArray['addresses']['houseNumberSuffix'] ?? null,
            'postalCode'        => $organizationArray['addresses']['postalCode'] ?? null,
            'locality'          => $organizationArray['addresses']['locality'] ?? null,
        ];
        $resource = [
            'name'               => $organizationArray['name'],
            'type'               => $type,
            'telephones'         => key_exists('telephones', $organizationArray) && $organizationArray['telephones'] ? [['name' => $organizationArray['telephones']['name'] ?? null, 'telephone' => $organizationArray['telephones']['telephone']]] : [],
            'emails'             => key_exists('emails', $organizationArray) && $organizationArray['emails'] ? [['name' => $organizationArray['emails']['name'] ?? null, 'email' => $organizationArray['emails']['email']]] : [],
            'addresses'          => key_exists('addresses', $organizationArray) && $organizationArray['addresses'] ? [$address] : [],
            'sourceOrganization' => $wrcOrganization['@id'],
        ];
        $result = $this->commonGroundService->createResource($resource, ['component' => 'cc', 'type' => 'organizations']);
        $wrcOrganization['contact'] = $result['@id'];
        $this->commonGroundService->saveResource($wrcOrganization, ['component' => 'wrc', 'type' => 'organizations', 'id' => $wrcOrganization['id']]);

        return $result;
    }

    /**
     * Updates an organization in the contact catalogue.
     *
     * @param string $id                The id of the organization to update
     * @param array  $organizationArray The updated properties of the organization to update
     *
     * @return array|false The updated organization object in the contact catalogue
     */
    public function updateOrganization(string $id, array $organizationArray)
    {
        $ccOrganization = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
        $wrcOrganization = $this->wrcService->saveOrganization($ccOrganization, $organizationArray);
        $address = [
            'name'              => $organizationArray['addresses']['name'] ?? $ccOrganization['addresses'][0]['name'] ?? null,
            'street'            => $organizationArray['addresses']['street'] ?? $ccOrganization['addresses'][0]['street'] ?? null,
            'houseNumber'       => $organizationArray['addresses']['houseNumber'] ?? $ccOrganization['addresses'][0]['houseNumber'] ?? null,
            'houseNumberSuffix' => $organizationArray['addresses']['houseNumberSuffix'] ?? $ccOrganization['addresses'][0]['houseNumberSuffix'] ?? null,
            'postalCode'        => $organizationArray['addresses']['postalCode'] ?? $ccOrganization['addresses'][0]['postalCode'] ?? null,
            'locality'          => $organizationArray['addresses']['locality'] ?? $ccOrganization['addresses'][0]['locality'] ?? null,
        ];
        $resource = [
            'name'               => $organizationArray['name'],
            'telephones'         => key_exists('telephones', $organizationArray) ? [['name' => $organizationArray['telephones']['name'] ?? $ccOrganization['telephones'][0]['name'] ?? null, 'telephone' => $organizationArray['telephones']['telephone']]] : [],
            'emails'             => key_exists('emails', $organizationArray) ? [['name' => $organizationArray['emails']['name'] ?? $ccOrganization['emails'][0]['name'] ?? null, 'email' => $organizationArray['emails']['email']]] : [],
            'addresses'          => key_exists('addresses', $organizationArray) ? [$address] : [],
            'sourceOrganization' => $wrcOrganization['@id'],
        ];
        $result = $this->commonGroundService->updateResource($resource, ['component' => 'cc', 'type' => 'organizations', 'id' => $id]);

        return $result;
    }

    /**
     * @param array       $body
     * @param string|null $id
     *
     * @return Response|null
     */
    public function checkUniqueOrganizationName(array $body, string $id = null): ?Response
    {
        $organizations = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['name' => $body['name'], 'type' => $body['type']])['hydra:member'];
        if (count($organizations) > 0 and $organizations[0]['id'] != $id) {
            return new Response(
                json_encode([
                    'message' => 'A '.$body['type'].' with this name already exists!',
                    'path'    => 'name',
                    'data'    => ['name' => $body['name']],
                ]),
                Response::HTTP_CONFLICT,
                ['content-type' => 'application/json']
            );
        }

        return null;
    }

    /**
     * @param string $id
     *
     * @return array|false|mixed|string|Response|null
     */
    public function checkIfOrganizationExists(string $id)
    {
        $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
        if (!$this->commonGroundService->isResource($organizationUrl)) {
            return new Response(
                json_encode([
                    'message' => 'This organization does not exist!',
                    'path'    => '',
                    'data'    => ['organization' => $organizationUrl],
                ]),
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'application/json']
            );
        }

        return $this->commonGroundService->getResource($organizationUrl);
    }

    /**
     * Deletes an organization.
     *
     * @param string      $id        The id of the organization to delete
     * @param string|null $programId The program related to the organization
     *
     * @return bool Whether or not the operation has been successful
     */
    public function deleteOrganization(string $id, ?string $programId): bool
    {
        try {
            $ccOrganization = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $id], [],false);

            //delete program
            if ($programId !== null) {
                $this->commonGroundService->deleteResource(null, ['component' => 'edu', 'type' => 'programs', 'id' => $programId]);
            }
            //delete organizations
            $wrcOrganizationId = explode('/', $ccOrganization['sourceOrganization']);
            $wrcOrganizationId = end($wrcOrganizationId);
            $this->commonGroundService->deleteResource(null, ['component' => 'wrc', 'type' => 'organizations', 'id' => $wrcOrganizationId]);

            foreach ($ccOrganization['telephones'] as $telephone) {
                $deleted = $this->commonGroundService->deleteResource($telephone);
                if ($deleted == false) {
                    throw new BadRequestPathException('Cant delete telephone.', 'organization');
                }
            }

            foreach ($ccOrganization['emails'] as $email) {
                $deleted = $this->commonGroundService->deleteResource($email);
                if ($deleted == false) {
                    throw new BadRequestPathException('Cant delete email.', 'organization');
                }
            }

            foreach ($ccOrganization['addresses'] as $address) {
                $deleted = $this->commonGroundService->deleteResource($address);
                if ($deleted == false) {
                    throw new BadRequestPathException('Cant delete address.', 'organization');
                }
            }

            foreach ($ccOrganization['persons'] as $person) {
                $deleted = $this->commonGroundService->deleteResource($person);
                if ($deleted == false) {
                    throw new BadRequestPathException('Cant delete person.', 'organization');
                }
            }
            $deleted = $this->commonGroundService->deleteResource($ccOrganization);
            if ($deleted == false) {
                throw new BadRequestPathException('Cant delete organization.', 'organization');
            }
            return true;
        } catch (\Throwable $e) {
            throw new BadRequestPathException('Cant delete organization.', 'organization');

        }
    }

    /**
     * Converts an address array from an employee into a address array that the contact catalogue can work with.
     *
     * @param array $addressArray The address part from an employee array
     *
     * @return array|null[] The resulting address array
     */
    public function convertAddress(array $addressArray): array
    {
        return [
            'name'              => key_exists('name', $addressArray) ? $addressArray['name'] : null,
            'street'            => key_exists('street', $addressArray) ? $addressArray['street'] : null,
            'houseNumber'       => key_exists('houseNumber', $addressArray) ? $addressArray['houseNumber'] : null,
            'houseNumberSuffix' => key_exists('houseNumberSuffix', $addressArray) ? $addressArray['houseNumberSuffix'] : null,
            'postalCode'        => key_exists('postalCode', $addressArray) ? $addressArray['postalCode'] : null,
            'locality'          => key_exists('locality', $addressArray) ? $addressArray['locality'] : null,
        ];
    }

    /**
     * Stores data for an employee in a person object in the contact catalogue.
     *
     * @param array $employee The employee object that was given as input
     *
     * @throws Exception Thrown if givenName is not provided
     *
     * @return array The resulting person array
     */
    public function employeeToPerson(array $employee): array
    {
        $employeePerson = $employee['person'];
        if ($this->commonGroundService->isResource($employeePerson)) {
            $employeePerson = $this->commonGroundService->getResource($employeePerson);
            $employeePerson['emails']['email'] = $employeePerson['emails'][0]['email'] ?? null;
        }
        $person = [
            'givenName'              => key_exists('givenName', $employeePerson) ? $employeePerson['givenName'] : new Exception('givenName must be provided'),
            'additionalName'         => key_exists('additionalName', $employeePerson) ? $employeePerson['additionalName'] : null,
            'familyName'             => key_exists('familyName', $employeePerson) ? $employeePerson['familyName'] : null,
            'birthday'               => key_exists('birthday', $employeePerson) ? $employeePerson['birthday'] : null,
            'gender'                 => key_exists('gender', $employeePerson) ? ($employeePerson['gender'] == 'X' ? null : $employeePerson['gender']) : null,
            'contactPreference'      => key_exists('contactPreference', $employeePerson) ? $employeePerson['contactPreference'] : null,
            'contactPreferenceOther' => key_exists('contactPreferenceOther', $employeePerson) ? $employeePerson['contactPreferenceOther'] : null,
            'telephones'             => key_exists('telephones', $employeePerson) && $employeePerson['telephones'][0]['telephone'] ? [['name' => 'telephone 1', 'telephone' => $employeePerson['telephones'][0]['telephone']]] : [],
            'emails'                 => key_exists('emails', $employeePerson) && $employeePerson['emails']['email'] ? [['name' => 'email 1', 'email' => $employeePerson['emails']['email']]] : [],
            'addresses'              => key_exists('addresses', $employeePerson) && $employeePerson['addresses'] ? [$this->convertAddress($employeePerson['addresses'])] : [],
            'availability'           => key_exists('availability', $employee) && $employee['availability'] ? $employee['availability'] : [],
            'organization'           => key_exists('organizationId', $employee) && $employee['organizationId'] ? '/organizations/'.$employee['organizationId'] : null,
        ]; //TODO: not sure if we want to set the organization for the person of an employee^
        $person['telephones'][] = key_exists('contactTelephone', $employeePerson) ? ['name' => 'contact telephone', 'telephone' => $employeePerson['contactTelephone']] : null;

        if ($person['givenName'] instanceof Exception) {
            throw $person['givenName'];
        }

        $person = $this->cleanResource($person);

        return $person;
    }

    /**
     * Creates a contact catalogue person for an employee object.
     *
     * @param array $person
     *
     * @throws Exception
     *
     * @return array The resulting person
     */
    public function createPersonForEmployee(array $employee): array
    {
        $person = $this->employeeToPerson($employee);

        return $this->eavService->saveObject($person, ['entityName' => 'people', 'componentCode' => 'cc']);
    }

    public function cleanPermissions(array $permissions): array
    {
        foreach ($permissions as $key => $value) {
            if ($key == 'hasPermissionToShareDataWithProviders') {
                $permissions['hasPermissionToShareDataWithAanbieders'] = $value;
                unset($permissions[$key]);
            }
        }

        return $permissions;
    }

    /**
     * Saves a person in the contact catalogue.
     *
     * @param Person                 $person      The person array to provide to the contact catalogue
     * @param StudentPermission|null $permissions
     *
     * @throws Exception
     *
     * @return array The result from the contact catalogue and EAV
     */
    public function createPerson(Person $person, ?StudentPermission $permissions = null): array
    {
        $this->entityManager->persist($person);
        $personArray = json_decode($this->serializer->serialize($person, 'json', ['ignored_attributes' => ['id']]), true);
        $permissionsArray = $permissions ? $this->cleanPermissions(json_decode($this->serializer->serialize($permissions, 'json', ['ignored_attributes' => ['id']]), true)) : [];
        $personArray = array_merge($personArray, $permissionsArray);
        $personArray = $this->cleanPerson($personArray);

        return $this->eavService->saveObject($personArray, ['entityName' => 'people', 'componentCode' => 'cc']);
        // This will not trigger notifications in nrc:
//        return $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);
    }

    public function cleanPerson(array $personArray): array
    {
        foreach ($personArray as $key => $value) {
            if ($key == 'organization' && $value && is_array($value)) {
                $personArray[$key] = '/organizations/'.$this->createOrganization($value, 'Provider')['id'];
            }
            if ($key == 'emails' && !isset($personArray['emails'][0]['email'])) { //do not remove this isset!
                $personArray[$key] = [$personArray[$key]];
            }
            if ($value !== false && !$value) {
                unset($personArray[$key]);
            }
        }

        return $personArray;
    }

    /**
     * Updates a person in the contac catalogue.
     *
     * @param string $id     The id of the person to update
     * @param array  $person The updated data of the person
     *
     * @throws Exception
     *
     * @return array The updated person object in the contact catalogue and EAV
     */
    public function updatePerson(string $id, array $person): array
    {
        $personUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $id]);
        $person = $this->cleanPerson($person);

        return $this->eavService->saveObject($person, ['entityName' => 'people', 'componentCode' => 'cc', 'self' => $personUrl]);
        // This will not trigger notifications in nrc:
//        return $this->commonGroundService->updateResource($person, ['component' => 'cc', 'type' => 'people', 'id' => $id]);
    }

    /**
     * Saves a person in the EAV.
     *
     * @param array $body      The data to store in the EAV
     * @param null  $personUrl The url of the person to save
     *
     * @throws Exception
     *
     * @return array The resulting object in the EAV
     */
    public function saveEavPerson(array $body, $personUrl = null): array
    {
        // Save the cc/people in EAV

        // unset org, emails, telephones etc (cant save subobjects)
        unset($body['availability']);
//        if (isset($body['organization'])) {
//            foreach ($body['organization'] as $key => $prop) {
//                var_dump($key);
//                var_dump(!is_int($key));
//                if (!is_int($key)) {
//                    unset($body['organization'][$key]);
//                }
//            }
//        }
        if (isset($personUrl)) {
            // Update
            $person = $this->eavService->saveObject($body, ['entityName' => 'people', 'componentCode' => 'cc', 'self' => $personUrl]);
        } else {
            // Create
            $person = $this->eavService->saveObject($body, ['entityName' => 'people', 'componentCode' => 'cc']);
        }

        return $person;
    }

    /**
     * Fetches a person from the contact catalogue with the EAV and returns it as an array.
     *
     * @param string $self The url of the person
     *
     * @throws Exception
     *
     * @return array The person array
     */
    public function getEavPerson(string $self): array
    {
        if ($this->eavService->hasEavObject($self)) {
            return $this->eavService->getObject(['entityName' => 'people', 'componentCode' => 'cc', 'self' => $self]);
        }

        return $this->commonGroundService->getResource($self);
    }

    /**
     * Deletes a person from the contact catalogue and deletes its info from the EAV.
     *
     * @param string $id The id of the person
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deletePerson(string $id): bool
    {
        return $this->eavService->deleteResource(null, ['component' => 'cc', 'type' => 'people', 'id' => $id]);
    }
}
