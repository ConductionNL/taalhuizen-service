<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\Example;
use App\Entity\LanguageHouse;
use App\Entity\Provider;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Jose\Component\Signature\Algorithm\EdDSA;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CCService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private ParameterBagInterface $parameterBag;
    private EAVService $eavService;
    private WRCService $wrcService;
    private EDUService $eduService;
    private UcService $ucService;
    private MrcService $mrcService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        ParameterBagInterface $parameterBag,
        EAVService $eavService,
        WRCService $wrcService,
        EDUService $eduService,
        UcService $ucService,
        MrcService $mrcService
    )
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
        $this->eavService = $eavService;
        $this->wrcService = $wrcService;
        $this->eduService = $eduService;
        $this->ucService = $ucService;
        $this->mrcService = $mrcService;
    }

    public function createOrganization(array $organizationArray, $type)
    {
        $wrcOrganization = $this->wrcService->createOrganization($organizationArray);
        $address = [
            'name' => 'Address of '.$organizationArray['name'],
            'street' => $organizationArray['address']['street'],
            'houseNumber' => $organizationArray['address']['houseNumber'],
            'houseNumberSuffix' => $organizationArray['address']['postalCode'],
            'postalCode' => $organizationArray['address']['postalCode'],
            'locality' => $organizationArray['address']['locality'],
        ];
        $resource = [
            'name' => $organizationArray['name'],
            'type' => $type,
            'telephones'        => key_exists('phoneNumber', $organizationArray) ? [['name' => 'Telephone of '.$organizationArray['name'], 'telephone' => $organizationArray['phoneNumber']]] : [],
            'emails'            => key_exists('email', $organizationArray) ? [['name' => 'Email of '.$organizationArray['name'], 'email' => $organizationArray['email']]] : [],
            'addresses'         => [$address],
            'sourceOrganization' => $wrcOrganization['@id'],
        ];
        $result = $this->commonGroundService->createResource($resource, ['component' => 'cc', 'type' => 'organizations']);
        $wrcOrganization['contact'] = $result['@id'];
        $this->commonGroundService->saveResource($wrcOrganization, ['component' => 'wrc', 'type' => 'organizations', 'id' => $wrcOrganization['id']]);

        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);
        return $this->createOrganizationObject($result, $type);
    }

    public function updateOrganization(string $id, array $organizationArray, $type)
    {
        $ccOrganization = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
        $wrcOrganization = $this->wrcService->saveOrganization($ccOrganization, $organizationArray);
        $address = [
            'name' => 'Address of '.$organizationArray['name'],
            'street' => $organizationArray['address']['street'],
            'houseNumber' => $organizationArray['address']['houseNumber'],
            'houseNumberSuffix' => $organizationArray['address']['postalCode'],
            'postalCode' => $organizationArray['address']['postalCode'],
            'locality' => $organizationArray['address']['locality'],
        ];
        $resource = [
            'name'              => $organizationArray['name'],
            'telephones'        => key_exists('phoneNumber', $organizationArray) ? [['name' => 'Telephone of '.$organizationArray['name'], 'telephone' => $organizationArray['phoneNumber']]] : [],
            'emails'            => key_exists('email', $organizationArray) ? [['name' => 'Email of '.$organizationArray['name'], 'email' => $organizationArray['email']]] : [],
            'addresses'         => [$address],
            'sourceOrganization' => $wrcOrganization['@id'],
        ];
        $result = $this->commonGroundService->updateResource($resource, ['component' => 'cc', 'type' => 'organizations', 'id' => $id]);

        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);
        return $this->createOrganizationObject($result, $type);
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

    public function createOrganizationObject(array $result, $type)
    {
        if ($type == 'Taalhuis') {
            $organization = new LanguageHouse();
        } else {
            $organization = new Provider();
        }

        $address = [
            'street' => $result['addresses'][0]['street'] ?? null,
            'houseNumber' => $result['addresses'][0]['houseNumber'] ?? null,
            'houseNumberSuffix' => $result['addresses'][0]['houseNumberSuffix'] ?? null,
            'postalCode' => $result['addresses'][0]['postalCode'] ?? null,
            'locality' => $result['addresses'][0]['locality'] ?? null,
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

    public function deleteOrganization(string $id): bool
    {
        $ccOrganization = $this->commonGroundService->getResource(['component'=>'cc', 'type' => 'organizations', 'id' => $id]);
        $program = $this->commonGroundService->getResourceList(['component' => 'edu','type'=>'programs'], ['provider' => $ccOrganization['@id']])["hydra:member"][0];
        $participants = $this->commonGroundService->getResourceList(['component'=>'edu', 'type' => 'participants'], ['program.id' => $program['id']])["hydra:member"];

        //delete userGroups
        $this->ucService->deleteUserGroups($ccOrganization['@id']);

        //delete employees
        $this->mrcService->deleteEmployees($ccOrganization['@id']);

        //delete participants
        $this->eduService->deleteParticipants($participants);

        //delete program
        $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'programs', 'id' => $program['id']]);

        //delete organizations
        $wrcOrganizationId = explode('/', $ccOrganization['sourceOrganization']);
        $wrcOrganizationId = end($wrcOrganizationId);
        $this->commonGroundService->deleteResource(null, ['component'=>'wrc', 'type' => 'organizations', 'id' => $wrcOrganizationId]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'telephones', 'id' => $ccOrganization['telephones'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'emails', 'id' => $ccOrganization['emails'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'addresses', 'id' => $ccOrganization['addresses'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'organizations', 'id' => $ccOrganization['id']]);

        return false;
    }

    public function getOrganizations($type): ArrayCollection
    {
        $organizations = new ArrayCollection();

        if ($type == 'Taalhuis') {
            $results = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'],['type' => 'Taalhuis'])["hydra:member"];
        } else {
            $results = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'],['type' => 'Aanbieder'])["hydra:member"];
        }

        foreach ($results as $result) {
            $organizations->add($this->createOrganizationObject($result, $type));
        }

        return $organizations;
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

    public function getOrganization(string $id, $type)
    {
        $result = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
        return $this->createOrganizationObject($result, $type);
    }

    public function convertAddress(array $addressArray): array
    {
        return [
            'street' => key_exists('street', $addressArray) ? $addressArray['street'] : null,
            'houseNumber' => key_exists('houseNumber', $addressArray) ? $addressArray['houseNumber'] : null,
            'houseNumberSuffix' => key_exists('houseNumberSuffix', $addressArray) ? $addressArray['postalCode'] : null,
            'postalCode' => key_exists('postalCode', $addressArray) ? $addressArray['postalCode'] : null,
            'locality' => key_exists('locality', $addressArray) ? $addressArray['locality'] : null,
        ];
    }

    public function cleanResource(array $array): array
    {
        foreach($array as $key=>$value){
            if(is_array($value)){
                $array[$key] = $this->cleanResource($value);
            } elseif(!$value){
                unset($array[$key]);
            }
        }
        return $array;
    }

    public function employeeToPerson(array $employeeArray, ?Employee $employee = null): array {
        $person = [
            'givenName' => key_exists('givenName', $employeeArray) ? $employeeArray['givenName'] : ($employee ? $employee->getGivenName() : new \Exception('givenName must be provided')),
            'additionalName'    => key_exists('additionalName', $employeeArray) ? $employeeArray['additionalName'] : null,
            'familyName'        => key_exists('familyName', $employeeArray) ? $employeeArray['familyName'] : null,
            'birthday'          => key_exists('dateOfBirth', $employeeArray) ? $employeeArray['dateOfBirth'] : null,
            'gender'            => key_exists('gender', $employeeArray) ? ($employeeArray['gender'] == "X" ? null: strtolower($employeeArray['gender'])): null,
            'contactPreference' =>
                key_exists('contactPreference', $employeeArray) ?
                    $employeeArray['contactPreference'] :
                    (key_exists('contactPreferenceOther', $employeeArray) ?
                        $employeeArray['contactPreferenceOther'] :
                        null
                    ),
            'telephones'        => key_exists('telephone', $employeeArray) && $employeeArray['telephone'] ? [['name' => 'telephone 1', 'telephone' => $employeeArray['telephone']]] : [],
            'emails'            => key_exists('email', $employeeArray) && $employeeArray['email'] ? [['name' => 'email 1', 'email' => $employeeArray['email']]] : ($employee && $employee->getEmail() ? [['name' => 'email 1', 'email' => $employee->getEmail()]] : []),
            'addresses'         => key_exists('address', $employeeArray) && $employeeArray['address'] ? [$this->convertAddress($employeeArray['address'])] : [],
            'availability'      => key_exists('availability', $employeeArray) && $employeeArray['availability'] ? $employeeArray['availability'] : [],
        ];
        $person['telephones'][] = key_exists('contactTelephone', $employeeArray) ? ['name' => 'contact telephone', 'telephone' => $employeeArray['contactTelephone']] : null;

        if($person['givenName'] instanceof \Exception){
            throw $person['givenName'];
        }

        $person = $this->cleanResource($person);
        return $person;
    }

    public function createPersonForEmployee(array $employee): array
    {
        $person = $this->employeeToPerson($employee);
        $person = $this->createPerson($person);

        return $person;
    }

    public function createPerson(array $person): array
    {
        return $this->eavService->saveObject($person, 'people', 'cc');
//        return $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);

    }

    public function updatePerson(string $id, array $person): array
    {
        return $this->commonGroundService->updateResource($person, ['component' => 'cc', 'type' => 'people', 'id' => $id]);
    }

    public function saveEavPerson($body, $personUrl = null) {
        // Save the cc/people in EAV
        if (isset($personUrl)) {
            // Update
            $person = $this->eavService->saveObject($body, 'people', 'cc', $personUrl);
        } else {
            // Create
            $person = $this->eavService->saveObject($body, 'people', 'cc');
        }
        return $person;
    }
}
