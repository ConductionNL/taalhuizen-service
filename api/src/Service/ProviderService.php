<?php

namespace App\Service;

use App\Entity\Provider;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;

class ProviderService
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
    )
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eduService = $eduService;
        $this->eavService = $eavService;
    }

    public function createProviderCCOrganization($provider, $providerWrc)
    {
        $providerCCOrganization['name'] = $provider['name'];
        $providerCCOrganization['type'] = 'Aanbieder';

        $providerCCOrganization['addresses'][0]['name'] = 'Address of ' . $provider['name'];
        $providerCCOrganization['addresses'][0] = $provider['address'];

        $providerCCOrganization['emails'][0]['name'] = 'Email of ' . $provider['name'];
        $providerCCOrganization['emails'][0]['email'] = $provider['email'];

        $providerCCOrganization['telephones'][0]['name'] = 'Telephone of ' . $provider['name'];
        $providerCCOrganization['telephones'][0]['telephone'] = $provider['phoneNumber'];

        //add source organization to cc organization
        $providerCCOrganization['sourceOrganization'] = $providerWrc['@id'];
        return $this->commonGroundService->saveResource($providerCCOrganization, ['component' => 'cc', 'type' => 'organizations']);
    }

    public function createProgram($provider, $providerWrc)
    {
        $program['name'] = 'Program of '.$provider['name'];
        $program['provider'] = $providerWrc['contact'];
        $this->commonGroundService->saveResource($program, ['component' => 'edu', 'type' => 'programs']);
    }

    public function createCoordinatorGroup($provider, $providerWrc)
    {
        $coordinator['organization'] = $providerWrc['contact'];
        $coordinator['name'] = 'AANBIEDER_COORDINATOR';
        $coordinator['description'] = 'userGroup coordinator of '.$provider['name'];
        $this->commonGroundService->saveResource($coordinator, ['component' => 'uc', 'type' => 'groups']);
    }

    public function createMentorGroup($provider, $providerWrc)
    {
        $mentor['organization'] = $providerWrc['contact'];
        $mentor['name'] = 'AANBIEDER_MENTOR';
        $mentor['description'] = 'userGroup mentor of '.$provider['name'];
        $this->commonGroundService->saveResource($mentor, ['component' => 'uc', 'type' => 'groups']);
    }

    public function createVolunteerGroup($provider, $providerWrc)
    {
        $volunteer['organization'] = $providerWrc['contact'];
        $volunteer['name'] = 'AANBIEDER_VOLUNTEER';
        $volunteer['description'] = 'userGroup mentor of '.$provider['name'];
        $this->commonGroundService->saveResource($volunteer, ['component' => 'uc', 'type' => 'groups']);
    }

    public function createProvider($provider)
    {
        // Save the provider
        $providerWrcOrganization['name'] = $provider['name'];
        $providerWrc = $this->commonGroundService->saveResource($providerWrcOrganization, ['component' => 'wrc', 'type' => 'organizations']);
        $providerCC = $this->createProviderCCOrganization($provider, $providerWrc);

        //add contact to wrc organization
        $providerWrcOrganization['contact'] = $providerCC['@id'];
        $providerWrc = $this->commonGroundService->saveResource($providerWrcOrganization, ['component' => 'wrc', 'type' => 'organizations']);

        //program
        $this->createProgram($provider, $providerWrc);
        //coordinator
        $this->createCoordinatorGroup($provider, $providerWrc);
        //mentor
        $this->createMentorGroup($provider, $providerWrc);
        //volunteer
        $this->createVolunteerGroup($provider, $providerWrc);

        // Add $providerCC to the $result['providerCC'] because this is convenient when testing or debugging (mostly for us)
        $result['provider'] = $providerCC;

        return $result;
    }

    public function getProvider($providerId)
    {
        $result['provider'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $providerId]);

        return $result;
    }

    public function getProviders()
    {
        $result['providers'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['type' => 'Aanbieder'])['hydra:member'];

        return $result;
    }

    public function updateProvider($provider, $providerId = null)
    {
        if (isset($providerId)) {
            // Update
            $providerCCOrganization['name'] = $provider['name'];

            $providerCCOrganization['addresses'][0]['name'] = 'Address of '.$provider['name'];
            $providerCCOrganization['addresses'][0] = $provider['address'];

            $providerCCOrganization['emails'][0]['name'] = 'Email of '.$provider['name'];
            $providerCCOrganization['emails'][0]['email'] = $provider['email'];

            $providerCCOrganization['telephones'][0]['name'] = 'Telephone of '.$provider['name'];
            $providerCCOrganization['telephones'][0]['telephone'] = $provider['phoneNumber'];

            $providerCC = $this->commonGroundService->updateResource($providerCCOrganization, ['component' => 'cc', 'type' => 'organizations', 'id' => $providerId]);

            $providerWrcOrganization['name'] = $provider['name'];
            $providerWrcOrganization['id'] = explode('/', $providerCC['sourceOrganization']);
            $providerWrcOrganization['id'] = end($providerWrcOrganization['id']);
            $providerWrc = $this->commonGroundService->updateResource($providerWrcOrganization, ['component' => 'wrc', 'type' => 'organizations', 'id' => $providerWrcOrganization['id']]);
        }

        // Add $providerCC to the $result['provider'] because this is convenient when testing or debugging (mostly for us)
        $result['provider'] = $providerCC;

        return $result;
    }

    public function deleteProvider($id)
    {
        $providerCC = $this->commonGroundService->getResource(['component'=>'cc', 'type' => 'organizations', 'id' => $id]);
        $program = $this->commonGroundService->getResourceList(['component' => 'edu', 'type'=>'programs'], ['provider' => $providerCC['@id']])['hydra:member'][0];
        $employees = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['organization' => $providerCC['@id']])['hydra:member'];
        $participants = $this->commonGroundService->getResourceList(['component'=>'edu', 'type' => 'participants'], ['program.id' => $program['id']])['hydra:member'];

        //delete employees
        $this->deleteEmployees($employees);

        //delete participants
        $this->deleteParticipants($participants);

        //delete program
        $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'programs', 'id' => $program['id']]);

        //delete organizations
        $providerWrcId = explode('/', $providerCC['sourceOrganization']);
        $providerWrcId = end($providerWrcId);
        $this->commonGroundService->deleteResource(null, ['component'=>'wrc', 'type' => 'organizations', 'id' => $providerWrcId]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'telephones', 'id' => $providerCC['telephones'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'emails', 'id' => $providerCC['emails'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'addresses', 'id' => $providerCC['addresses'][0]['id']]);
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'organizations', 'id' => $providerCC['id']]);
        $result['provider'] = $providerCC;

        return $result;
    }

    public function handleResult($provider, $userRoles = null)
    {
        $resource = new Provider();
        if (isset($userRoles)) {
            $resource->setName($userRoles['name']);
        } else {
            $address = [
                'street'            => $provider['addresses'][0]['street'] ?? null,
                'houseNumber'       => $provider['addresses'][0]['houseNumber'] ?? null,
                'houseNumberSuffix' => $provider['addresses'][0]['houseNumberSuffix'] ?? null,
                'postalCode'        => $provider['addresses'][0]['postalCode'] ?? null,
                'locality'          => $provider['addresses'][0]['locality'] ?? null,
            ];
            $resource->setAddress($address);
            $resource->setEmail($provider['emails'][0]['email'] ?? null);
            $resource->setPhoneNumber($provider['telephones'][0]['telephone'] ?? null);
            $resource->setName($provider['name']);
            $resource->setType($provider['type'] ?? null);
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
        if ($participants > 0) {
            foreach ($participants as $participant) {
                $person = $this->commonGroundService->getResource($participant['person']);
                $educationEvents = $this->commonGroundService->getResource($participant['educationEvents']);
                $results = $this->commonGroundService->getResource($participant['results']);
                $participantGroups = $this->commonGroundService->getResource($participant['participantGroups']);
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
        }

        return false;
    }

    public function getUserRolesByProvider($id): array
    {
        $organizationUrl = $this->commonGroundService->cleanUrl(['component'=>'cc', 'type'=>'organizations', 'id'=>$id]);
        $userRolesByProvider = $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'groups'], ['organization'=>$organizationUrl])['hydra:member'];

        return $userRolesByProvider;
    }
}
