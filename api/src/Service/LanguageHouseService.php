<?php

namespace App\Service;


use App\Entity\LanguageHouse;
use App\Entity\Provider;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LanguageHouseService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private CommonGroundService $commonGroundService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
    }

    public function createLanguageHouse($languageHouse)
    {
        // Save the provider
        $languageHouseWrcOrganization['name'] = $languageHouse['name'];
        $languageHouseWrc = $this->commonGroundService->saveResource($languageHouseWrcOrganization, ['component' => 'wrc', 'type' => 'organizations']);

        $languageHouseCCOrganization['name'] = $languageHouse['name'];
        $languageHouseCCOrganization['type'] = 'Taalhuis';

        $languageHouseCCOrganization['addresses'][0]['name'] = 'Address of ' . $languageHouse['name'];
        $languageHouseCCOrganization['addresses'][0] = $languageHouse['address'];

        $languageHouseCCOrganization['emails'][0]['name'] = 'Email of ' . $languageHouse['name'];
        $languageHouseCCOrganization['emails'][0]['email'] = $languageHouse['email'];

        $languageHouseCCOrganization['telephones'][0]['name'] = 'Telephone of ' . $languageHouse['name'];
        $languageHouseCCOrganization['telephones'][0]['telephone'] = $languageHouse['phoneNumber'];

        //add source organization to cc organization
        $languageHouseCCOrganization['sourceOrganization'] = $languageHouseWrc['@id'];
        $languageHouseCC = $this->commonGroundService->saveResource($languageHouseCCOrganization, ['component' => 'cc', 'type' => 'organizations']);

        //add contact to wrc organization
        $languageHouseWrcOrganization['contact'] = $languageHouseCC['@id'];
        $languageHouseWrc = $this->commonGroundService->saveResource($languageHouseWrcOrganization, ['component' => 'wrc', 'type' => 'organizations']);

        // Add $providerCC to the $result['providerCC'] because this is convenient when testing or debugging (mostly for us)
        $result['languageHouse'] = $languageHouseCC;

        return $result;
    }

    public function getLanguageHouse($languageHouseId)
    {
        $result['languageHouse'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        return $result;
    }

    public function getLanguageHouses()
    {
        $result['languageHouses'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'],['type' => 'Taalhuis'])["hydra:member"];
        return $result;
    }

    public function updateLanguageHouse($languageHouse, $languageHouseId = null)
    {
        if (isset($languageHouseId)) {
            // Update
            $languageHouseCCOrganization['name'] = $languageHouse['name'];

            $languageHouseCCOrganization['addresses'][0]['name'] = 'Address of ' . $languageHouse['name'];
            $languageHouseCCOrganization['addresses'][0] = $languageHouse['address'];

            $languageHouseCCOrganization['emails'][0]['name'] = 'Email of ' . $languageHouse['name'];
            $languageHouseCCOrganization['emails'][0]['email'] = $languageHouse['email'];

            $languageHouseCCOrganization['telephones'][0]['name'] = 'Telephone of ' . $languageHouse['name'];
            $languageHouseCCOrganization['telephones'][0]['telephone'] = $languageHouse['phoneNumber'];

            $languageHouseCC = $this->commonGroundService->updateResource($languageHouseCCOrganization, ['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);

            $languageHouseWrcOrganization['name'] = $languageHouse['name'];
            $languageHouseWrcOrganization['id'] = explode('/', $languageHouseCC['sourceOrganization']);
            $languageHouseWrcOrganization['id'] = end($languageHouseWrcOrganization['id']);
            $providerWrc = $this->commonGroundService->updateResource($languageHouseWrcOrganization, ['component' => 'wrc', 'type' => 'organizations', 'id' => $languageHouseWrcOrganization['id']]);
        }

        // Add $providerCC to the $result['providerCC'] because this is convenient when testing or debugging (mostly for us)
        $result['languageHouse'] = $languageHouseCC;

        return $result;
    }


    public function deleteLanguageHouse($id)
    {
        $languageHouseCC = $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'organization', 'id' => $id]);
        $languageHouseWrcId = explode('/', $languageHouseCC['sourceOrganization']);
        $languageHouseWrcId = end($languageHouseWrcId);
        $this->commonGroundService->deleteResource(null, ['component'=>'wrc', 'type' => 'organization', 'id' => $languageHouseWrcId]);

        $result['languageHouse'] = $languageHouseCC;
        return $result;
    }

    public function handleResult($languageHouse)
    {
        $resource = new LanguageHouse();
        $resource->setAddress($languageHouse['address']);
        $resource->setEmail($languageHouse['email']);
        $resource->setPhoneNumber($languageHouse['phoneNumber']);
        $resource->setName($languageHouse['name']);
        $this->entityManager->persist($resource);
        return $resource;
    }

    public function createLanguageHouseObject($languageHouse)
    {
        $resource = new LanguageHouse();
        $resource->setAddress($languageHouse['addresses']);
        $resource->setEmail($languageHouse['emails'][0]['email']);
        $resource->setPhoneNumber($languageHouse['telephones'][0]['telephone']);
        $resource->setName($languageHouse['name']);
        $resource->setType($languageHouse['type']);
        $this->entityManager->persist($resource);
        return $resource;
    }

}
