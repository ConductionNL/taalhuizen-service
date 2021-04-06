<?php

namespace App\Service;

use App\Entity\Attribute;
use App\Entity\Example;
use App\Entity\ObjectEntity;
use App\Entity\Value;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SensioLabs\Security\Exception\HttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use Doctrine\Common\Collections\Collection;

class ObjectEntityService
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
