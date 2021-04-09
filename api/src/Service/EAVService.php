<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EAVService
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

    public function saveObject(array $body, $entityName, $componentCode = 'eav', $self = null, $eavId = null) {
        $body['componentCode'] = $componentCode;
        $body['entityName'] = $entityName;
        if (isset($self)) {
            $body['@self'] = $self;
        } elseif (isset($eavId)) {
            $body['objectEntityId'] = $eavId;
        }
        return $this->commonGroundService->createResource($body, ['component' => 'eav', 'type' => 'object_communications']);
    }

    public function getObject($entityName, $self = null, $componentCode = 'eav', $eavId = null) {
        $body['doGet'] = true;
        $body['componentCode'] = $componentCode;
        $body['entityName'] = $entityName;
        if (isset($self)) {
            $body['@self'] = $self;
        } elseif (isset($eavId)) {
            $body['objectEntityId'] = $eavId;
        } else {
            return '[EAVService] a get to the eav component needs a @self or an eavId!';
        }
        return $this->commonGroundService->createResource($body,['component' => 'eav', 'type' => 'object_communications']);
    }
}
