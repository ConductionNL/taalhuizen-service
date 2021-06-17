<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;

class EAVService
{
    private CommonGroundService $commonGroundService;

    /**
     * EAVService constructor.
     *
     * @param CommonGroundService $commonGroundService
     */
    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
    }

    /**
     * A function for saving an object in/with the eav-component with or without extra variables that are defined in the eav-component.
     * This will create a new object or update an existing one and return that object.
     * This can only be used for objects that are defined as EAV/entity in the eav-component.
     * When updating an existing obejct the self or eavId is required.
     *
     * @param array $body    the body for creating or updating the object. This body should contain at least the entityName and could also contain the componentCode (default = 'eav'). Defined as EAV/entity with type in the eav-component.
     * @param array $eavInfo an array containing at least an $eavInfo['entityName'] (example='people') and could also contain the $eavInfo['componentCode'] (default = 'eav', example='cc'). Defined as EAV/entity type in the eav-component. Can also be used to update an existing eav object with $eavInfo['self'] = the (component @id, not @eav) url to an existing object. Or with $eavInfo['eavId'] = the id of an eav object.
     *
     * @throws Exception
     *
     * @return array the saved object.
     */
    public function saveObject(array $body, array $eavInfo): array
    {
        if (!isset($eavInfo['entityName'])) {
            throw new Exception('[EAVService] needs an entityName in the $eavInfo array in order to save an object in/with the EAV!');
        }
        $body['entityName'] = $eavInfo['entityName'];
        if (!isset($eavInfo['componentCode'])) {
            $body['componentCode'] = 'eav';
        } else {
            $body['componentCode'] = $eavInfo['componentCode'];
        }
        if (isset($eavInfo['self'])) {
            $body['@self'] = $eavInfo['self'];
        } elseif (isset($eavInfo['eavId'])) {
            $body['objectEntityId'] = $eavInfo['eavId'];
        }
        $result = $this->commonGroundService->createResource($body, ['component' => 'eav', 'type' => 'object_communications']);
        $result['@id'] = str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $result['@id']);

        return $result;
    }

    /**
     * A function for searching an object (with its extra variables that are defined in the eav-component) with the given query params.
     * This can only be used for objects that are defined as EAV/entity in the eav-component.
     *
     * @param string      $entityName    the name of the entity to get. Defined as EAV/entity in the eav-component.
     * @param string|null $componentCode the component code of the entity to get. Defined as EAV/entity in the eav-component. Default is 'eav' itself.
     * @param array|null  $query         the search/query parameters.
     *
     * @return array the found object.
     */
    public function getObjectList(string $entityName, ?string $componentCode = 'eav', ?array $query = []): array
    {
        $body['doGet'] = true;
        $body['componentCode'] = $componentCode;
        $body['entityName'] = $entityName;
        $body['query'] = $query;

        $result = $this->commonGroundService->createResource($body, ['component' => 'eav', 'type' => 'object_communications']);
        $result['@id'] = str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $result['@id']);

        return $result;
    }

    /**
     * A function for getting an object from/with the eav-component with its extra variables that are defined in the eav-component.
     * This can only be used for objects that are defined as EAV/entity in the eav-component.
     * The self or eavId is required to get an object from/with the eav-component.
     *
     * @param array $eavInfo an array containing at least an $eavInfo['entityName'] (example='people') and could also contain the $eavInfo['componentCode'] (default = 'eav', example='cc'). Defined as EAV/entity type in the eav-component. Also needs to have either $eavInfo['self'] = the (component @id, not @eav) url to an existing object. Or $eavInfo['eavId'] = the id of an eav object.
     *
     * @throws Exception
     *
     * @return array the object array.
     */
    public function getObject(array $eavInfo): array
    {
        $body['doGet'] = true;
        if (!isset($eavInfo['entityName'])) {
            throw new Exception('[EAVService] needs an entityName in the $eavInfo array in order to get an object from/with the EAV!');
        }
        $body['entityName'] = $eavInfo['entityName'];
        if (!isset($eavInfo['componentCode'])) {
            $body['componentCode'] = 'eav';
        } else {
            $body['componentCode'] = $eavInfo['componentCode'];
        }
        if (isset($eavInfo['self'])) {
            $body['@self'] = $eavInfo['self'];
        } elseif (isset($eavInfo['eavId'])) {
            $body['objectEntityId'] = $eavInfo['eavId'];
        } else {
            throw new Exception('[EAVService] a get to the eav component needs a self or an eavId in the $eavInfo array!');
        }
        $result = $this->commonGroundService->createResource($body, ['component' => 'eav', 'type' => 'object_communications']);
        // Hotfix, createResource adds this to the front of an @id, but eav already returns @id with this in front:
        $result['@id'] = str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $result['@id']);

        return $result;
    }

    /**
     * This function deletes an eav object from the eav-component.
     * Note that this will not delete any object that this eav object was connected to, but only the info stored in the eav!
     * This can only be used for objects that are defined as EAV/entity in the eav-component.
     * Only eavId or [entityName, self & componentCode] is required to use this function.
     *
     * @param string|null $eavId         the id of an eav object you want to delete.
     * @param string|null $entityName    the name of the entity to delete. Defined as EAV/entity in the eav-component.
     * @param string|null $self          the (component @id, not @eav) url to an existing object from which you want to delete all eav data.
     * @param string|null $componentCode the component code of the entity to delete. Defined as EAV/entity in the eav-component. Default is 'eav' itself.
     *
     * @throws Exception
     *
     * @return bool true if the eav object was deleted.
     */
    public function deleteObject(string $eavId = null, string $entityName = null, string $self = null, ?string $componentCode = 'eav'): bool
    {
        if (isset($eavId)) {
            $object['eavId'] = $eavId;
        } else {
            $object = $this->getObject(['entityName' => $entityName, 'componentCode' => $componentCode, 'self' => $self]);
        }
        $object = $this->commonGroundService->getResource(['component' => 'eav', 'type' => 'object_entities', 'id' => $object['eavId']]);
        $this->commonGroundService->deleteResource($object);

        return true;
    }

    /**
     * This function deletes an eav object from the eav-component and deletes any object that this eav object is connected to.
     * This can only be used for objects that are defined as EAV/entity in the eav-component.
     * This function expects you to always give an component & type in the url array that matches the EAV/entity in the eav-component.
     * And than either give an id in the url array or give a resource with an @id or id set.
     *
     * @param array|null $resource               the resource you want to delete.
     * @param array|null $url                    an array used to create an url to the resource you want to delete, containing at least component and type, but could also contain the id.
     * @param array|null $deleteResourceSettings an array that can be used to set the params for the commongroundService->deleteResource function. Default: async = false, autowire = true, events = true.
     *
     * @throws Exception
     *
     * @return bool true if the eav object and any object connected to this eav object was deleted.
     */
    public function deleteResource(?array $resource, array $url = null, array $deleteResourceSettings = null): bool
    {
        $deleteResourceSettings = $this->checkDeleteResourceSettings($deleteResourceSettings);

        if (!isset($url['component']) || !isset($url['type'])) {
            throw new Exception('[EAVService] needs a component and a type in $url to delete the eav Object of a resource!');
        }
        if (isset($resource['@id'])) {
            $self = $resource['@id'];
        } elseif (isset($url['id'])) {
            $self = $this->commonGroundService->cleanUrl($url);
        } else {
            throw new Exception('[EAVService] needs a $resource[\'@id\'] or $url[\'id\'] to delete the eav Object of a resource!');
        }
        if ($this->hasEavObject($self)) {
            $eavResult = $this->deleteObject(null, $url['type'], $self, $url['component']);
        } else {
            $eavResult = true;
        }
        $result = $this->commonGroundService->deleteResource($resource, $url, $deleteResourceSettings['async'], $deleteResourceSettings['autowire'], $deleteResourceSettings['events']);
        if ($eavResult and $result) {
            return true;
        }

        return false;
    }

    /**
     * Check if any of the settings for commongroundService->deleteResource function are set in the given array and return the default options if not.
     *
     * @param array|null $deleteResourceSettings
     * @return array an array that can be used to set the params for the commongroundService->deleteResource function. Default: async = false, autowire = true, events = true.
     */
    private function checkDeleteResourceSettings(?array $deleteResourceSettings): array
    {
        return [
            'async'    => $deleteResourceSettings['async'] ?? false,
            'autowire' => $deleteResourceSettings['autowire'] ?? true,
            'events'   => $deleteResourceSettings['events'] ?? true,
        ];
    }

    /**
     * This function checks if there is an existing eav object connected to/for the object uri (or entityName + id + componentCode).
     * This can only be used for objects that are defined as EAV/entity in the eav-component.
     *
     * @param string|null $uri           the url to an object you want to check if it has an eav object connected to it in the eav-component.
     * @param string|null $entityName    the entity name of the object you want to check. Defined as EAV/entity in the eav-component.
     * @param string|null $id            the id to the object you want to check.
     * @param string|null $componentCode the component code of the object you want to check. Defined as EAV/entity in the eav-component. Default is 'eav' itself.
     *
     * @throws Exception
     *
     * @return bool true if there is an existing eav object for the given uri (or entityName + id + componentCode).
     */
    public function hasEavObject(?string $uri, string $entityName = null, string $id = null, ?string $componentCode = 'eav'): bool
    {
        if (!isset($uri)) {
            // If you want to check with an $id instead of an $uri, you need to give at least the entityName as well
            if (isset($id) && isset($entityName)) {
                $uri = $componentCode.'/'.$entityName.'/'.$id;
            } else {
                throw new Exception('[EAVService] needs an uri or an entityName + id to check if an eav Object exists!');
            }
        } elseif (!str_contains($uri, 'http')) {
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
