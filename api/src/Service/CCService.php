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

}
