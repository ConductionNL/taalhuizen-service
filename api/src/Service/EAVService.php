<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class EAVService
{
    private $em;
    private $commonGroundService;

    public function __construct(EntityManagerInterface $em, CommonGroundService $commonGroundService)
    {
        $this->em = $em;
        $this->commonGroundService = $commonGroundService;
    }

    public function saveObject(array $body, $entityName, $componentCode = 'eav', $self = null, $eavId = null) {
        $body['componentCode'] = $componentCode;
        $body['entityName'] = $entityName;
        if (isset($self)) {
            $body['@self'] = $self;
        } elseif (isset($eavId)) {
            $body['objectEntityId'] = $eavId;
        }
        $result = $this->commonGroundService->createResource($body, ['component' => 'eav', 'type' => 'object_communications']);
        $result['@id'] = str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $result['@id']);
        return $result;
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
            throw new Exception('[EAVService] a get to the eav component needs a @self or an eavId!');
        }
        $result = $this->commonGroundService->createResource($body, ['component' => 'eav', 'type' => 'object_communications']);
        // Hotfix, createResource adds this to the front of an @id, but eav already returns @id with this in front:
        $result['@id'] = str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $result['@id']);
        return $result;
    }

    public function deleteObject($eavId = null, $entityName = null, $self = null, $componentCode = 'eav') {
        if (isset($eavId)) {
            $object['eavId'] = $eavId;
        } else {
            $object = $this->getObject($entityName, $self, $componentCode);
        }
        $object = $this->commonGroundService->getResource(['component' => 'eav', 'type' => 'object_entities', 'id' => $object['eavId']]);
        $this->commonGroundService->deleteResource($object);
        return true;
    }

    public function deleteResource($resource, $url = null, $async = false, $autowire = true, $events = true) {
        if (!isset($url['component']) || !isset($url['type'])) {
            throw new Exception('[EAVService] needs a component and a type in $url to delete the eav Object of a resource!');
        }
        if (isset($resource['@id'])) {
            $self = $resource['@id'];
        } elseif(isset($url['id'])) {
            $self = $this->commonGroundService->cleanUrl($url);
        } else {
            throw new Exception('[EAVService] needs a $resource[\'@id\'] or $url[\'id\'] to delete the eav Object of a resource!');
        }
        if ($this->hasEavObject($self)) {
            $eavResult = $this->deleteObject(null, $url['type'], $self, $url['component']);
        } else {
            $eavResult = true;
        }
        $result = $this->commonGroundService->deleteResource($resource, $url, $async, $autowire, $events);
        if ($eavResult and $result) {
            return true;
        }
        return false;
    }

    public function hasEavObject($uri, $entityName = null, $id = null, $componentCode = 'eav') {
        if (!isset($uri)) {
            // If you want to check with an $id instead of an $uri, you need to give at least the entityName as well
            if (isset($id) && isset($entityName)) {
                $uri = $componentCode.'/'.$entityName.'/'.$id;
            } else {
                throw new Exception('[EAVService] needs an uri or an entityName + id to check if an eav Object exists!');
            }
        } elseif (!str_contains($uri, 'http')){
            // Make sure the $uri contains a url^, else:
            throw new Exception('[EAVService] can not check if an eav Object exists with an uri that is not an url!');
        }

        $result = $this->commonGroundService->getResourceList(['component' => 'eav', 'type' => 'object_entities'], ['uri' => $uri])['hydra:member'];
        if (count($result) == 1) {
            return true;
        }
        return false;
    }
}
