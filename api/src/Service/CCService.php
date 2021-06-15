<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\LanguageHouse;
use App\Entity\Provider;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;

class CCService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private WRCService $wrcService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = new EAVService($commonGroundService);
        $this->wrcService = new WRCService($entityManager, $commonGroundService);
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
            } elseif (!$value) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @param array  $result
     * @param string $type
     *
     * @return LanguageHouse|Provider
     */
    public function createOrganizationObject(array $result, string $type)
    {
        if ($type == 'Taalhuis') {
            $organization = new LanguageHouse();
        } else {
            $organization = new Provider();
        }

        $address = [
            'street'            => $result['addresses'][0]['street'] ?? null,
            'houseNumber'       => $result['addresses'][0]['houseNumber'] ?? null,
            'houseNumberSuffix' => $result['addresses'][0]['houseNumberSuffix'] ?? null,
            'postalCode'        => $result['addresses'][0]['postalCode'] ?? null,
            'locality'          => $result['addresses'][0]['locality'] ?? null,
        ];
        $organization->setName($result['name']);
        $organization->setAddress($address);
        $organization->setEmail($result['emails'][0]['email'] ?? null);
        $organization->setPhoneNumber($result['telephones'][0]['telephone'] ?? null);
        $organization->setType($result['type'] ?? null);

        $this->entityManager->persist($organization);
        $organization->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($organization);

        return $organization;
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
            'name'              => 'Address of '.$organizationArray['name'],
            'street'            => $organizationArray['address']['street'],
            'houseNumber'       => $organizationArray['address']['houseNumber'],
            'houseNumberSuffix' => $organizationArray['address']['postalCode'],
            'postalCode'        => $organizationArray['address']['postalCode'],
            'locality'          => $organizationArray['address']['locality'],
        ];
        $resource = [
            'name'               => $organizationArray['name'],
            'type'               => $type,
            'telephones'         => key_exists('phoneNumber', $organizationArray) ? [['name' => 'Telephone of '.$organizationArray['name'], 'telephone' => $organizationArray['phoneNumber']]] : [],
            'emails'             => key_exists('email', $organizationArray) ? [['name' => 'Email of '.$organizationArray['name'], 'email' => $organizationArray['email']]] : [],
            'addresses'          => [$address],
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
     * @param array         $employeeArray The employee object that was given as input
     * @param Employee|null $employee      The employee the data relates to
     *
     * @throws Exception Thrown if givenName is not provided
     *
     * @return array The resulting person array
     */
    public function employeeToPerson(array $employeeArray, ?Employee $employee = null): array
    {
        $person = [
            'givenName'         => key_exists('givenName', $employeeArray) ? $employeeArray['givenName'] : ($employee ? $employee->getGivenName() : new Exception('givenName must be provided')),
            'additionalName'    => key_exists('additionalName', $employeeArray) ? $employeeArray['additionalName'] : null,
            'familyName'        => key_exists('familyName', $employeeArray) ? $employeeArray['familyName'] : null,
            'birthday'          => key_exists('dateOfBirth', $employeeArray) ? $employeeArray['dateOfBirth'] : null,
            'gender'            => key_exists('gender', $employeeArray) ? ($employeeArray['gender'] == 'X' ? null : strtolower($employeeArray['gender'])) : null,
            'contactPreference' => key_exists('contactPreference', $employeeArray) ?
                    $employeeArray['contactPreference'] :
                    (
                        key_exists('contactPreferenceOther', $employeeArray) ?
                        $employeeArray['contactPreferenceOther'] :
                        null
                    ),
            'telephones'        => key_exists('telephone', $employeeArray) && $employeeArray['telephone'] ? [['name' => 'telephone 1', 'telephone' => $employeeArray['telephone']]] : [],
            'emails'            => key_exists('email', $employeeArray) && $employeeArray['email'] ? [['name' => 'email 1', 'email' => $employeeArray['email']]] : ($employee && $employee->getEmail() ? [['name' => 'email 1', 'email' => $employee->getEmail()]] : []),
            'addresses'         => key_exists('address', $employeeArray) && $employeeArray['address'] ? [$this->convertAddress($employeeArray['address'])] : [],
            'availability'      => key_exists('availability', $employeeArray) && $employeeArray['availability'] ? $employeeArray['availability'] : [],
        ];
        $person['telephones'][] = key_exists('contactTelephone', $employeeArray) ? ['name' => 'contact telephone', 'telephone' => $employeeArray['contactTelephone']] : null;

        if ($person['givenName'] instanceof Exception) {
            throw $person['givenName'];
        }

        $person = $this->cleanResource($person);

        return $person;
    }

    /**
     * Creates a contact catalogue person for an employee object.
     *
     * @param array $employee The employee to create a person for
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
    public function createPerson(array $person): array
    {
        return $this->eavService->saveObject($person, ['entityName' => 'people', 'componentCode' => 'cc']);
        // This will not trigger notifications in nrc:
//        return $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);
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
            $person = $this->eavService->saveObject($body, ['entityName' => 'groups', 'componentCode' => 'edu']);
        }

        return $person;
    }
}
