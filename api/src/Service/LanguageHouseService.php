<?php

namespace App\Service;


use App\Entity\LanguageHouse;
use App\Entity\Provider;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\Test\TestHttpServer;

class LanguageHouseService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private CommonGroundService $commonGroundService;
    private EDUService $eduService;
    private MrcService $mrcService;
    private EAVService $eavService;

    public function __construct
    (
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        ParameterBagInterface $parameterBag,
        EDUService $eduService,
        MrcService $mrcService,
        EAVService $eavService
    ){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
        $this->eduService = $eduService;
        $this->mrcService = $mrcService;
        $this->eavService = $eavService;
    }

    public function getLanguageHouse($languageHouseId)
    {
        $result['languageHouse'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        return $result;
    }

    public function getLanguageHouses(): array
    {
        $result['languageHouses'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'],['type' => 'Taalhuis'])["hydra:member"];
        return $result;
    }

    public function getLanguageHouseUserGroups($id)
    {
        //get provider url
        $LanguageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organization', 'id' => $id]);
        //get provider groups
        $userGroups = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'],['organization' => $LanguageHouseUrl])['hydra:member'];
        $userRoles = [];
        foreach ($userGroups as $userGroup){
            $userRoles['id'] = $userGroup['id'];
            $userRoles['name'] = $userGroup['name'];
        }
        return $userRoles;
    }

    public function getUserRolesByLanguageHouse($id): array
    {
        $organizationUrl = $this->commonGroundService->cleanUrl(['component'=>'cc', 'type'=>'organizations', 'id'=>$id]);
        $userRolesByLanguageHouse =  $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'groups'], ['organization'=>$organizationUrl])['hydra:member'];

        return $userRolesByLanguageHouse;
    }
}
