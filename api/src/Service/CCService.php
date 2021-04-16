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

    public function saveOrganization(array $body, $type = null, $sourceOrgurl = null){
        if (isset($type)) $body['type'] = $type;
        if (isset($sourceOrgurl)) $body['sourceOrganization'];
            return $this->commonGroundService->saveResource($body, ['component' => 'cc', 'type' => 'organization']);
    }

    public function getOrganization($id){
        return $result = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
    }

    public function savePerson($person){

        return $person;
    }

}
