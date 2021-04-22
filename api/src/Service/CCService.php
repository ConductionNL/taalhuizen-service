<?php

namespace App\Service;

use App\Entity\Example;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CCService
{
    private $em;
    private $commonGroundService;
    private $params;

    public function __construct(EntityManagerInterface $em, CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
    }

    public function saveOrganization(Example $example){
            $resource = $example->getData();
            $resource['organization'] = '/organizations/'.$resource['organization'];
            return $this->commonGroundService->saveResource($resource, ['component' => 'wrc', 'type' => 'organization']);
    }

    public function getOrganization($id){
        return $result = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
    }

    public function savePerson($person){

        return $person;
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

    public function employeeToPerson(array $employee): array {
        $person = [
            'givenName' => $employee['givenName'],
            'additionalName' => key_exists('additionalName', $employee) ? $employee['additionalName'] : null,
            'familyName' => key_exists('familyName', $employee) ? $employee['familyName'] : null,
            'birthday' => key_exists('dateOfBirth', $employee) ? $employee['dateOfBirt'] : null,
            'gender' => key_exists('gender', $employee) ? $employee['gender'] : null,
            'contactPreference' =>
                key_exists('contactPreference', $employee) ?
                    $employee['contactPreference'] :
                    (key_exists('contactPreferenceOther', $employee) ?
                        $employee['contactPreferenceOther'] :
                        null
                    ),
            'telephones' => key_exists('telephone', $employee) ? [['name' => 'telephone 1', 'telephone' => $employee['telephone']]] : [],
            'emails' => key_exists('email', $employee) ? [['name' => 'email 1', 'emial' => $employee['email']]] : [],
            'addresses' => key_exists('address', $employee) ? [$this->convertAddress($employee['address'])] : [],
        ];
        $person['telephones'][] = key_exists('contactTelephone', $employee) ? ['name' => 'contact telephone', 'telephone' => $employee['contactTelephone']] : null;
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
        return $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);

    }

    public function updatePerson(string $id, array $person): array
    {
        return $this->commonGroundService->updateResource($person, ['component' => 'cc', 'type' => 'people', 'id' => $id]);
    }

}
