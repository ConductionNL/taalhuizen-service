<?php


namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EDUService
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

    //@todo uitwerken
    public function saveProgram($organization){
        $program = [];
        $program['name'] = $organization['name'];
        $program['provider'] = $organization['@id'];

        $program = $this->commonGroundService->saveResource($program,['component' => 'edu','type'=>'programs']);
        return $program;
    }

    public function getProgram($organization){
        return $result = $this->commonGroundService->getResource(['component' => 'edu','type'=>'programs'], ['provider' => $organization['@id']])['hydra:member'];
    }

    public function hasProgram($organization){
        $result = $this->commonGroundService->getResource(['component' => 'edu','type'=>'programs'], ['provider' => $organization['@id']])['hydra:member'];
        if (count($result) > 1){
            return true;
        }
        return false;
    }
}
