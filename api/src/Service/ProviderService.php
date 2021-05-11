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
    private EDUService $eduService;
    private EAVService $eavService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        ParameterBagInterface $parameterBag,
        EDUService $eduService,
        EAVService $eavService
    ){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
        $this->eduService = $eduService;
        $this->eavService = $eavService;
    }

    public function getProvider($providerId)
    {
        $result['provider'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $providerId]);
        return $result;
    }

    public function getProviders()
    {
        $result['providers'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'],['type' => 'Aanbieder'])["hydra:member"];
        return $result;
    }

    public function getProviderUserGroups($id)
    {
        //get provider url
        $providerUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organization', 'id' => $id]);
        //get provider groups
        $userGroups = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'],['organization' => $providerUrl])['hydra:member'];
        $userRoles = [];
        foreach ($userGroups as $userGroup){
            $userRoles['id'] = $userGroup['id'];
            $userRoles['name'] = $userGroup['name'];
        }
        return $userRoles;
    }

    public function getUserRolesByProvider($id): array
    {
        $organizationUrl = $this->commonGroundService->cleanUrl(['component'=>'cc', 'type'=>'organizations', 'id'=>$id]);
        $userRolesByProvider =  $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'groups'], ['organization'=>$organizationUrl])['hydra:member'];

        return $userRolesByProvider;
    }

}
