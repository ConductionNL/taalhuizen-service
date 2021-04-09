<?php

namespace App\Service;

use App\Entity\Example;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

// TODO:delete this service
class ExampleService
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

    public function saveExample(Example $example) {
        $resource = $example->getData();
        $resource['organization'] = '/organizations/'.$resource['organization'];
        return $this->commonGroundService->saveResource($resource, ['component' => 'wrc', 'type' => 'images']);
    }

    public function getExample(Example $example) {
        $query['limit'] = 1000;
        return $this->commonGroundService->getResourceList(['component' => 'wrc', 'type' => 'images'],$query)['hydra:member'];
    }
}
