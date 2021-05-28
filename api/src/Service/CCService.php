<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\Example;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CCService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private ParameterBagInterface $parameterBag;
    private EAVService $eavService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, ParameterBagInterface $parameterBag, EAVService $eavService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
        $this->eavService = $eavService;
    }

    public function saveOrganization(array $body, $type = null, $sourceOrgurl = null){
        if (isset($type)) $body['type'] = $type;
        if (isset($sourceOrgurl)) $body['sourceOrganization'];
            return $this->commonGroundService->saveResource($body, ['component' => 'cc', 'type' => 'organization']);
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
        // This will not trigger notifications in nrc:
//        return $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);
    }

    public function updatePerson(string $id, array $person): array
    {
        $personUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $id]);
        return $this->eavService->saveObject($person, 'people', 'cc', $personUrl);
        // This will not trigger notifications in nrc:
//        return $this->commonGroundService->updateResource($person, ['component' => 'cc', 'type' => 'people', 'id' => $id]);
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
