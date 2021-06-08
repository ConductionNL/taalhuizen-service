<?php

namespace App\Service;

use App\Entity\LanguageHouse;
use App\Entity\Provider;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;

class LanguageHouseService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EDUService $eduService;
    private EAVService $eavService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        EDUService $eduService,
        EAVService $eavService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eduService = $eduService;
        $this->eavService = $eavService;
    }

    public function handleLanguagueHouseCC($languageHouse, $languageHouseWrc)
    {
        $languageHouseCCOrganization['name'] = $languageHouse['name'];
        $languageHouseCCOrganization['type'] = 'Taalhuis';

        if (isset($languageHouse['address'])) {
            $languageHouseCCOrganization['addresses'][0]['name'] = 'Address of ' . $languageHouse['name'];
            $languageHouseCCOrganization['addresses'][0] = $languageHouse['address'];
        }

        if (isset($languageHouse['email'])) {
            $languageHouseCCOrganization['emails'][0]['name'] = 'Email of ' . $languageHouse['name'];
            $languageHouseCCOrganization['emails'][0]['email'] = $languageHouse['email'];
        }

        if (isset($languageHouse['phoneNumber'])) {
            $languageHouseCCOrganization['telephones'][0]['name'] = 'Telephone of ' . $languageHouse['name'];
            $languageHouseCCOrganization['telephones'][0]['telephone'] = $languageHouse['phoneNumber'];
        }

        //add source organization to cc organization
        $languageHouseCCOrganization['sourceOrganization'] = $languageHouseWrc['@id'];

        return $this->commonGroundService->saveResource($languageHouseCCOrganization, ['component' => 'cc', 'type' => 'organizations']);
    }

    public function createLanguageHouse($languageHouse)
    {
        // Save the provider
        $languageHouseWrcOrganization['name'] = $languageHouse['name'];
        $languageHouseWrc = $this->commonGroundService->saveResource($languageHouseWrcOrganization, ['component' => 'wrc', 'type' => 'organizations']);

        $languageHouseCC = $this->handleLanguagueHouseCC($languageHouse, $languageHouseWrc);

        //add contact to wrc organization
        $languageHouseWrcOrganization['contact'] = $languageHouseCC['@id'];
        $languageHouseWrc = $this->commonGroundService->saveResource($languageHouseWrcOrganization, ['component' => 'wrc', 'type' => 'organizations']);

        //program
        $program['name'] = 'Program of '.$languageHouse['name'];
        $program['provider'] = $languageHouseWrc['contact'];

        $this->commonGroundService->saveResource($program, ['component' => 'edu', 'type' => 'programs']);

        //make Usergroups for roles
        //coordinator
        $this->createCoordinatorGroup($languageHouse, $languageHouseWrc);

        //employee
        $this->createEmployeeGroup($languageHouse, $languageHouseWrc);

        // Add $providerCC to the $result['providerCC'] because this is convenient when testing or debugging (mostly for us)
        $result['languageHouse'] = $languageHouseCC;

        return $result;
    }

    public function createEmployeeGroup($languageHouse, $languageHouseWrc)
    {
        $employee['organization'] = $languageHouseWrc['contact'];
        $employee['name'] = 'TAALHUIS_EMPLOYEE';
        $employee['description'] = 'userGroup employee of '.$languageHouse['name'];
        $this->commonGroundService->saveResource($employee, ['component' => 'uc', 'type' => 'groups']);
    }

    public function createCoordinatorGroup($languageHouse, $languageHouseWrc)
    {
        $coordinator['organization'] = $languageHouseWrc['contact'];
        $coordinator['name'] = 'TAALHUIS_COORDINATOR';
        $coordinator['description'] = 'userGroup coordinator of '.$languageHouse['name'];
        $this->commonGroundService->saveResource($coordinator, ['component' => 'uc', 'type' => 'groups']);
    }

    public function getLanguageHouse($languageHouseId)
    {
        $result['languageHouse'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);

        return $result;
    }

    public function getLanguageHouses()
    {
        $result['languageHouses'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['type' => 'Taalhuis'])['hydra:member'];

        return $result;
    }

    public function updateLanguageHouse($languageHouse, $languageHouseId = null): array
    {
        if (isset($languageHouseId)) {
            // Update
            $languageHouseCCOrganization['name'] = $languageHouse['name'];

            if (isset($languageHouse['address'])) {
                $languageHouseCCOrganization['addresses'][0]['name'] = 'Address of ' . $languageHouse['name'];
                $languageHouseCCOrganization['addresses'][0] = $languageHouse['address'];
            }
            if (isset($languageHouse['email'])) {
                $languageHouseCCOrganization['emails'][0]['name'] = 'Email of ' . $languageHouse['name'];
                $languageHouseCCOrganization['emails'][0]['email'] = $languageHouse['email'];
            }
            if (isset($languageHouse['phoneNumber'])) {
                $languageHouseCCOrganization['telephones'][0]['name'] = 'Telephone of ' . $languageHouse['name'];
                $languageHouseCCOrganization['telephones'][0]['telephone'] = $languageHouse['phoneNumber'];
            }

            $languageHouseCC = $this->commonGroundService->updateResource($languageHouseCCOrganization, ['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);

            $languageHouseWrcOrganization['name'] = $languageHouse['name'];
            $languageHouseWrcOrganization['id'] = explode('/', $languageHouseCC['sourceOrganization']);
            $languageHouseWrcOrganization['id'] = end($languageHouseWrcOrganization['id']);
            $this->commonGroundService->updateResource($languageHouseWrcOrganization, ['component' => 'wrc', 'type' => 'organizations', 'id' => $languageHouseWrcOrganization['id']]);
        }

        // Add $providerCC to the $result['providerCC'] because this is convenient when testing or debugging (mostly for us)
        $result['languageHouse'] = $languageHouseCC;

        return $result;
    }

    public function deleteLanguageHouse($id)
    {
        $languageHouseCC = $this->commonGroundService->getResource(['component'=>'cc', 'type' => 'organizations', 'id' => $id]);
        $program = $this->commonGroundService->getResourceList(['component' => 'edu', 'type'=>'programs'], ['provider' => $languageHouseCC['@id']])['hydra:member'][0];
        $employees = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['organization' => $languageHouseCC['@id']])['hydra:member'];
        $participants = $this->commonGroundService->getResourceList(['component'=>'edu', 'type' => 'participants'], ['program.id' => $program['id']])['hydra:member'];

        //delete employees
        $this->deleteEmployees($employees);

        //delete participants
        $this->deleteParticipants($participants);

        //delete program
        $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'programs', 'id' => $program['id']]);

        //delete organizations
        $languageHouseWrcId = explode('/', $languageHouseCC['sourceOrganization']);
        $languageHouseWrcId = end($languageHouseWrcId);
        $this->commonGroundService->deleteResource(null, ['component'=>'wrc', 'type' => 'organizations', 'id' => $languageHouseWrcId]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'telephones', 'id' => $languageHouseCC['telephones'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'emails', 'id' => $languageHouseCC['emails'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'addresses', 'id' => $languageHouseCC['addresses'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'organizations', 'id' => $languageHouseCC['id']]);

        $result['languageHouse'] = $languageHouseCC;

        return $result;
    }

    public function handleResult($languageHouse, $userRoles = null)
    {
        $resource = new LanguageHouse();
        if (isset($userRoles)) {
            $resource->setName($userRoles['name']);
        } else {
            $address = [
                'street'            => $languageHouse['addresses'][0]['street'] ?? null,
                'houseNumber'       => $languageHouse['addresses'][0]['houseNumber'] ?? null,
                'houseNumberSuffix' => $languageHouse['addresses'][0]['houseNumberSuffix'] ?? null,
                'postalCode'        => $languageHouse['addresses'][0]['postalCode'] ?? null,
                'locality'          => $languageHouse['addresses'][0]['locality'] ?? null,
            ];
            $resource->setAddress($address);
            $resource->setEmail($languageHouse['emails'][0]['email'] ?? null);
            $resource->setPhoneNumber($languageHouse['telephones'][0]['telephone'] ?? null);
            $resource->setName($languageHouse['name']);
            $resource->setType($languageHouse['type'] ?? null);
        }
        $this->entityManager->persist($resource);

        return $resource;
    }

    public function deleteEmployees($employees): bool
    {
        if ($employees > 0) {
            foreach ($employees as $employee) {
                $person = $this->commonGroundService->getResource($employee['person']);
                $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'people', 'id' => $person['id']]);
                $this->commonGroundService->deleteResource(null, ['component'=>'mrc', 'type'=>'employees', 'id'=>$employee['id']]);
            }
        }

        return false;
    }

    public function deleteParticipants($participants): bool
    {
        foreach ($participants as $participant) {
            $person = $this->commonGroundService->getResource($participant['person']);
            $results = $participant['results'];
            $educationEvents = $participant['educationEvents'];
            $participantGroups = $participant['participantGroups'];
            foreach ($educationEvents as $educationEvent) {
                $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'education_events', 'id' => $educationEvent['id']]);
            }
            foreach ($results as $result) {
                $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'results', 'id' => $result['id']]);
            }
            foreach ($participantGroups as $participantGroup) {
                $this->eduService->deleteGroup($participantGroup['id']);
            }
            $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'people', 'id' => $person['id']]);
            $this->eavService->deleteResource(null, ['component'=>'edu', 'type'=>'participants', 'id'=>$participant['id']]);
        }

        return false;
    }

    public function getUserRolesByLanguageHouse($id): array
    {
        $organizationUrl = $this->commonGroundService->cleanUrl(['component'=>'cc', 'type'=>'organizations', 'id'=>$id]);
        $userRolesByLanguageHouse = $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'groups'], ['organization'=>$organizationUrl])['hydra:member'];

        return $userRolesByLanguageHouse;
    }
}
