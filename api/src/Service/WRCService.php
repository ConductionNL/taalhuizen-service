<?php


namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WRCService
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
        if (isset($body['address'])) unset($body['address']);
        if (isset($body['email'])) unset($body['email']);
        if (isset($body['phoneNumber'])) unset($body['phoneNumber']);

        $result = $this->commonGroundService->saveResource($body,['component' => 'wrc', 'type' => 'organizations']);

        return $result;
    }

    public function getOrganization($id){
        return $result = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
    }


}
