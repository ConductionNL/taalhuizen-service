<?php

namespace App\Service;

use App\Entity\Example;
use App\Entity\LanguageHouse;
use App\Entity\Provider;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
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

    public function createOrganization(array $organizationArray): Provider
    {
        $wrcOrganization = $this->wrcService->createOrganization($organizationArray);
        $email = [
            'name' => 'Email of '.$organizationArray['name'],
            'email' => $organizationArray['email'],
        ];
        $telephone = [
            'name' => 'Telephone of '.$organizationArray['name'],
            'telephone' => $organizationArray['phoneNumber'],
        ];
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
            'type' => 'Aanbieder',
            'addresses' => [$address],
            'emails' => [$email],
            'telephones' => [$telephone],
            'sourceOrganization' => $wrcOrganization['@id'],
        ];
        $result = $this->commonGroundService->createResource($resource, ['component' => 'cc', 'type' => 'organizations']);
        $wrcOrganization['contact'] = $result['@id'];
        $this->commonGroundService->saveResource($wrcOrganization, ['component' => 'wrc', 'type' => 'organizations', 'id' => $wrcOrganization['id']]);

        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result);
        return $this->createOrganizationObject($result);
    }

    public function updateOrganization(string $id, array $organizationArray, $languageHouse = null): Provider
    {
        $ccOrganization = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
        $wrcOrganization = $this->wrcService->saveOrganization($ccOrganization, $organizationArray);
        $email = [
            'name' => 'Email of '.$organizationArray['name'],
            'email' => $organizationArray['email'],
        ];
        $telephone = [
            'name' => 'Telephone of '.$organizationArray['name'],
            'telephone' => $organizationArray['phoneNumber'],
        ];
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
            'type' => 'Aanbieder',
            'addresses' => [$address],
            'emails' => [$email],
            'telephones' => [$telephone],
            'sourceOrganization' => $wrcOrganization['@id'],
        ];
        $result = $this->commonGroundService->updateResource($resource, ['component' => 'cc', 'type' => 'organizations', 'id' => $id]);

        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result);
        return $this->createOrganizationObject($result);
    }

    public function createOrganizationObject(array $result, $languageHouse = null): Provider
    {
        if (isset($languageHouse)) {
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

    public function getOrganization($id){
        return $result = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
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

    public function employeeToPerson(array $employee): array {
        $person = [
            'givenName' => $employee['givenName'],
            'additionalName'    => key_exists('additionalName', $employee) ? $employee['additionalName'] : null,
            'familyName'        => key_exists('familyName', $employee) ? $employee['familyName'] : null,
            'birthday'          => key_exists('dateOfBirth', $employee) ? $employee['dateOfBirth'] : null,
            'gender'            => key_exists('gender', $employee) ? ($employee['gender'] == "X" ? null: strtolower($employee['gender'])): null,
            'contactPreference' =>
                key_exists('contactPreference', $employee) ?
                    $employee['contactPreference'] :
                    (key_exists('contactPreferenceOther', $employee) ?
                        $employee['contactPreferenceOther'] :
                        null
                    ),
            'telephones'        => key_exists('telephone', $employee) ? [['name' => 'telephone 1', 'telephone' => $employee['telephone']]] : [],
            'emails'            => key_exists('email', $employee) ? [['name' => 'email 1', 'email' => $employee['email']]] : [],
            'addresses'         => key_exists('address', $employee) ? [$this->convertAddress($employee['address'])] : [],
            'availability'      => key_exists('availability', $employee) ? $employee['availability'] : [],
        ];
        $person['telephones'][] = key_exists('contactTelephone', $employee) ? ['name' => 'contact telephone', 'telephone' => $employee['contactTelephone']] : null;

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
