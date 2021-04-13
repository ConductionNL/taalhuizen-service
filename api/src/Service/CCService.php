<?php

namespace App\Service;

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

    public function saveOrganization(array $body){
        //save address
        if (isset($body['address'])){
            $body['adresses'][0] = $this->commonGroundService->saveResource($body['address'],['component' => 'cc', 'type' => 'addresses']);
            unset($body['address']);
        }
        //save email
        if (isset($body['email'])){
            $body['emails'][0] = $this->commonGroundService->saveResource($body['email'],['component' => 'cc', 'type' => 'emails']);
            unset($body['email']);
        }
        //save telephone
        if (isset($body['phoneNumber'])){
            $body['telephones'][0] = $this->commonGroundService->saveResource($body['phoneNumber'],['component' => 'cc', 'type' => 'telephones']);
            unset($body['phoneNumber']);
        }

        $result = $this->commonGroundService->saveResource($body,['component' => 'cc', 'type' => 'organizations']);

        return $result;
    }

    public function getOrganization($id){
        return $result = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
    }

}
