<?php

namespace App\Service;

use App\Entity\Document;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
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

    public function createOrganization(array $organizationArray)
    {
        $resource = [
            'name' => $organizationArray['name'],
        ];
        $result = $this->commonGroundService->createResource($resource, ['component' => 'wrc', 'type' => 'organizations']);

        return $result;
    }

    public function saveOrganization(array $ccOrganization, array $organizationArray)
    {
        $organization = $this->commonGroundService->getResource($ccOrganization['sourceOrganization']);
        $resource = [
            'name' => $organizationArray['name'],
        ];
        $result = $this->commonGroundService->updateResource($resource, ['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);

        return $result;
    }

    public function getOrganization($id)
    {
        return $result = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
    }

    public function setContact($input)
    {
        if (isset($input['studentId'])) {
            $id = $input['studentId'];
            $idArray = explode('/', $id);
            $id = end($idArray);
            $contact = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id]);
        } elseif (isset($input['aanbiederEmployeeId'])) {
            $id = $input['aanbiederEmployeeId'];
            $idArray = explode('/', $id);
            $id = end($idArray);
            $contact = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]);
        } else {
            throw new Exception('No studentId or aanbiederEmployeetId given');
        }

        return $contact;
    }

    public function handleDocumentProps($input)
    {
        $requiredProps = ['base64data', 'filename'];
        foreach ($requiredProps as $prop) {
            if (!isset($input[$prop])) {
                throw new Exception('No '.$prop.' has been given');
            }
        }
    }

    /**
     * @param array $input
     *
     * @throws Exception
     *
     * @return Document
     */
    public function createDocument(array $input): Document
    {
        $this->handleDocumentProps($input);
        if (isset($input['studentId']) && isset($input['aanbiederEmployeeId'])) {
            throw new Exception('Both studentId and aanbiederEmployeeId are given, please give one type of id');
        }

        //set contact
        $contact = $this->setContact($input);

        $document['name'] = $input['filename'];
        $document['base64'] = $input['base64data'];
        $document['contact'] = $contact;

        try {
            $document = $this->commonGroundService->saveResource($document, ['component' => 'wrc', 'type' => 'documents']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this->createDocumentObject($document);
    }

    public function getDocumentId(array $input)
    {
        if (isset($input['studentDocumentId']) && isset($input['aanbiederEmployeeDocumentId'])) {
            throw new Exception('Both studentDocumentId and aanbiederEmployeeDocumentId are given, please give one type of id');
        }
        if (isset($input['studentDocumentId'])) {
            $id = $input['studentDocumentId'];
        } elseif (isset($input['aanbiederEmployeeDocumentId'])) {
            $id = $input['aanbiederEmployeeDocumentId'];
        } else {
            throw new Exception('No studentDocumentId or aanbiederEmployeeDocumentId given');
        }
        if (strpos($id, '/') !== false) {
            $idArray = explode('/', $id);
            $id = end($idArray);
        }

        return $id;
    }

    /**
     * @throws Exception
     */
    public function downloadDocument($input): Document
    {
        $id = $this->getDocumentId($input);

        try {
            $document = $this->commonGroundService->getResource($this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'documents', 'id' => $id]));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this->createDocumentObject($document);
    }

    /**
     * @param array $input
     *
     * @throws Exception
     *
     * @return Document|null
     */
    public function removeDocument(array $input): ?Document
    {
        $id = $this->getDocumentId($input);

        try {
            $this->commonGroundService->deleteResource(null, ['component'=>'wrc', 'type' => 'documents', 'id' => $id]);

            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param array $document
     *
     * @return Document
     */
    public function createDocumentObject(array $document): Document
    {
        $documentObject = new Document();
        $documentObject->setFilename(isset($document['name']) ? $document['name'] : null);
        $documentObject->setBase64data(isset($document['base64']) ? $document['base64'] : null);
        $documentObject->setDateCreated(isset($document['dateCreated']) ? $document['dateCreated'] : null);
        if (isset($document['contact'])) {
            $contactArray = explode('/', $document['contact']);
            $contact = end($contactArray);
            strpos($document['contact'], 'participant') !== false ? $documentObject->setStudentId($contact) : $documentObject->setAanbiederEmployeeId($contact);
            strpos($document['contact'], 'participant') !== false ? $documentObject->setStudentDocumentId('/documents/'.$document['id']) : $documentObject->setAanbiederEmployeeDocumentId('/documents/'.$document['id']);
        }
        $this->em->persist($documentObject);
        $documentObject->setId(Uuid::getFactory()->fromString($document['id']));
        $this->em->persist($documentObject);

        return $documentObject;
    }

    /**
     * @param string $id
     *
     * @throws Exception
     *
     * @return Document
     */
    public function getDocument(string $id): Document
    {
        try {
            $document = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'documents', 'id' => $id]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this->createDocumentObject($document);
    }

    /**
     * @param string|null $contact
     *
     * @throws Exception
     *
     * @return ArrayCollection
     */
    public function getDocuments(?string $contact = null): ArrayCollection
    {
        try {
            $documents = $this->commonGroundService->getResourceList(['component' => 'wrc', 'type' => 'documents'], ['contact' => $contact])['hydra:member'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $documentObjects = new ArrayCollection();

        foreach ($documents as $document) {
            $documentObjects->add($this->createDocumentObject($document));
        }

        return $documentObjects;
    }
}
