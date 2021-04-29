<?php

namespace App\Service;


use App\Entity\Provider;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProviderService
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

    public function createProvider($provider)
    {
        // Save the provider
        $providerWrcOrganization['name'] = $provider['name'];
        $providerWrc = $this->commonGroundService->saveResource($providerWrcOrganization, ['component' => 'wrc', 'type' => 'organizations']);

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
        $providerCC = $this->commonGroundService->saveResource($providerCCOrganization, ['component' => 'cc', 'type' => 'organizations']);

        //add contact to wrc organization
        $providerWrcOrganization['contact'] = $providerCC['@id'];
        $providerWrc = $this->commonGroundService->saveResource($providerWrcOrganization, ['component' => 'wrc', 'type' => 'organizations']);

        // Add $providerCC to the $result['providerCC'] because this is convenient when testing or debugging (mostly for us)
        $result['provider'] = $providerCC;

        return $result;
    }

    public function getProvider($providerId)
    {
        $result['provider'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $providerId])["hydra:member"];
        return $result;
    }

    public function getProviders()
    {
        $result['providers'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'],['type' => 'Aanbieder'])["hydra:member"];
        return $result;
    }

    public function updateProvider($provider, $providerId = null)
    {
        if (isset($providerId)) {
            // Update
            $providerCCOrganization['name'] = $provider['name'];

            $providerCCOrganization['addresses'][0]['name'] = 'Address of ' . $provider['name'];
            $providerCCOrganization['addresses'][0] = $provider['address'];

            $providerCCOrganization['emails'][0]['name'] = 'Email of ' . $provider['name'];
            $providerCCOrganization['emails'][0]['email'] = $provider['email'];

            $providerCCOrganization['telephones'][0]['name'] = 'Telephone of ' . $provider['name'];
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
        $providerCC = $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'organization', 'id' => $id]);
        $providerWrcId = explode('/', $providerCC['sourceOrganization']);
        $providerWrcId = end($providerWrcId);
        $this->commonGroundService->deleteResource(null, ['component'=>'wrc', 'type' => 'organization', 'id' => $providerWrcId]);

        $result['provider'] = $providerCC;
        return $result;
    }

    public function handleResult($provider)
    {
        $resource = new Provider();
        $resource->setAddress($provider['address']);
        $resource->setEmail($provider['email']);
        $resource->setPhoneNumber($provider['phoneNumber']);
        $resource->setName($provider['name']);
        $this->entityManager->persist($resource);
        return $resource;
    }

    public function createProviderObject($provider)
    {
        $resource = new Provider();
        $resource->setAddress($provider['addresses']);
        $resource->setEmail($provider['emails'][0]['email']);
        $resource->setPhoneNumber($provider['telephones'][0]['telephone']);
        $resource->setName($provider['name']);
        $this->entityManager->persist($resource);
        return $resource;
    }

}
