<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Email;
use App\Entity\Employee;
use App\Entity\LanguageHouse;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Provider;
use App\Entity\Telephone;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
        foreach ($array as $key=>$value) {
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
     * @param string $type The type of organization to fetch
     *
     * @return ArrayCollection a collection of all organizations of the provided type
     */
    public function getOrganizations(string $type): ArrayCollection
    {
        $organizations = new ArrayCollection();

        if ($type == 'Taalhuis') {
            $results = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['type' => 'Taalhuis'])['hydra:member'];
        } else {
            $results = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['type' => 'Aanbieder'])['hydra:member'];
        }

        foreach ($results as $result) {
            $organizations->add($this->createOrganizationObject($result, $type));
        }

        return $organizations;
    }

    /**
     * Fetches an organization from the contact catalogue and returns it as an object for the type of organization.
     *
     * @param string $id   The id of the organization to fetch
     * @param string $type The type of organization
     *
     * @return LanguageHouse|Provider The organization that has been fetched
     */
    public function getOrganization(string $id, string $type)
    {
        $result = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);

        return $this->createOrganizationObject($result, $type);
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
            'telephones'         => key_exists('telephones', $organizationArray) ? [['name' => $organizationArray['telephones']['name'], 'telephone' => $organizationArray['telephones']['telephone']]] : [],
            'emails'             => key_exists('emails', $organizationArray) ? [['name' => $organizationArray['emails']['name'], 'email' => $organizationArray['emails']['email']]] : [],
            'addresses'          => key_exists('addresses', $organizationArray) ? [$address] : [],
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
            'name'              => 'Address of '.$organizationArray['name'],
            'street'            => $organizationArray['address']['street'],
            'houseNumber'       => $organizationArray['address']['houseNumber'],
            'houseNumberSuffix' => $organizationArray['address']['postalCode'],
            'postalCode'        => $organizationArray['address']['postalCode'],
            'locality'          => $organizationArray['address']['locality'],
        ];
        $resource = [
            'name'               => $organizationArray['name'],
            'telephones'         => key_exists('phoneNumber', $organizationArray) ? [['name' => 'Telephone of '.$organizationArray['name'], 'telephone' => $organizationArray['phoneNumber']]] : [],
            'emails'             => key_exists('email', $organizationArray) ? [['name' => 'Email of '.$organizationArray['name'], 'email' => $organizationArray['email']]] : [],
            'addresses'          => [$address],
            'sourceOrganization' => $wrcOrganization['@id'],
        ];
        $result = $this->commonGroundService->updateResource($resource, ['component' => 'cc', 'type' => 'organizations', 'id' => $id]);

        return $result;
    }

    /**
     * Deletes an organization.
     *
     * @param string $id        The id of the organization to delete
     * @param string $programId The program related to the organization
     *
     * @return bool Whether or not the operation has been successful
     */
    public function deleteOrganization(string $id, string $programId): bool
    {
        $ccOrganization = $this->commonGroundService->getResource(['component'=>'cc', 'type' => 'organizations', 'id' => $id]);
        //delete program
        $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'programs', 'id' => $programId]);
        //delete organizations
        $wrcOrganizationId = explode('/', $ccOrganization['sourceOrganization']);
        $wrcOrganizationId = end($wrcOrganizationId);
        $this->commonGroundService->deleteResource(null, ['component'=>'wrc', 'type' => 'organizations', 'id' => $wrcOrganizationId]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'telephones', 'id' => $ccOrganization['telephones'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'emails', 'id' => $ccOrganization['emails'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'addresses', 'id' => $ccOrganization['addresses'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'organizations', 'id' => $ccOrganization['id']]);

        return true;
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
     *@throws Exception Thrown if givenName is not provided
     *
     * @return array The resulting person array
     */
    public function employeeToPerson(array $employee): array
    {
        $employeePerson = $employee['person'];
        $person = [
            'givenName'              => key_exists('givenName', $employeePerson) ? $employeePerson['givenName'] : new Exception('givenName must be provided'),
            'additionalName'         => key_exists('additionalName', $employeePerson) ? $employeePerson['additionalName'] : null,
            'familyName'             => key_exists('familyName', $employeePerson) ? $employeePerson['familyName'] : null,
            'birthday'               => key_exists('dateOfBirth', $employeePerson) ? $employeePerson['dateOfBirth'] : null,
            'gender'                 => key_exists('gender', $employeePerson) ? ($employeePerson['gender'] == 'X' ? null : $employeePerson['gender']) : null,
            'contactPreference'      => key_exists('contactPreference', $employeePerson) ? $employeePerson['contactPreference'] : null,
            'contactPreferenceOther' => key_exists('contactPreferenceOther', $employeePerson) ? $employeePerson['contactPreferenceOther'] : null,
            'telephones'             => key_exists('telephones', $employeePerson) && $employeePerson['telephones'][0]['telephone'] ? [['name' => 'telephone 1', 'telephone' => $employeePerson['telephones'][0]['telephone']]] : [],
            'emails'                 => key_exists('emails', $employeePerson) && $employeePerson['emails']['email'] ? [['name' => 'email 1', 'email' => $employeePerson['emails']['email']]] : [],
            'addresses'              => key_exists('addresses', $employeePerson) && $employeePerson['addresses'] ? [$this->convertAddress($employeePerson['addresses'])] : [],
            'availability'           => key_exists('availability', $employee) && $employee['availability'] ? $employee['availability'] : [],
        ];
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
        $person = $this->createPerson($person);

        return $person;
    }

    /**
     * Saves a person in the contact catalogue.
     *
     * @param array $person The person array to provide to the contact catalogue
     *
     * @throws Exception
     *
     * @return array The result from the contact catalogue and EAV
     */
    public function createPerson(Person $person): array
    {
        $this->entityManager->persist($person);
        $personArray = json_decode($this->serializer->serialize($person, 'json', ['ignored_attributes' => ['id']]), true);
        foreach($personArray as $key => $value){
            if(!$value)
                unset($personArray[$key]);
            if($key == 'emails'){
                $personArray[$key] = [$personArray[$key]];
            }
        }

        return $this->eavService->saveObject($personArray, ['entityName' => 'people', 'componentCode' => 'cc']);
        // This will not trigger notifications in nrc:
//        return $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);
    }

    public function createPersonObject(array $personArray): Person
    {
        $person = new Person();
        $person->setGivenName($personArray['givenName']);
        $person->setFamilyName($personArray['familyName']);
        $person->setAdditionalName($personArray['additionalName']);

        $this->entityManager->persist($person);

        return $person;
//        $person->setGender($personArray['gender']);
//        $person->setContactPreference($personArray['contactPreference']);
//        $person->setBirthday($personArray['birthday']);
//        $person->setEmails($this->createEmailObject($personArray['emails'][0]));
//        $person->setGivenName($personArray['givenName']);
//        $person->setGivenName($personArray['givenName']);
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
        if (isset($personUrl)) {
            // Update
            $person = $this->eavService->saveObject($body, ['entityName' => 'people', 'componentCode' => 'cc', 'self' => $personUrl]);
        } else {
            // Create
            $person = $this->eavService->saveObject($body, ['entityName' => 'people', 'componentCode' => 'cc']);
        }

        return $person;
    }
}
