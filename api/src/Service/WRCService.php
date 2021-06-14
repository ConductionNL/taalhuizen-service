<?php

namespace App\Service;

use App\Entity\Document;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class WRCService
{
    private EntityManagerInterface $em;
    private CommonGroundService $commonGroundService;

    public function __construct(
        EntityManagerInterface $em,
        CommonGroundService $commonGroundService
    ) {
        $this->em = $em;
        $this->commonGroundService = $commonGroundService;
    }

    /**
     * This function creates a wrc/organization with the given array.
     *
     * @param array $organizationArray Array with organizations data
     *
     * @return array|false Returns the created organization
     */
    public function createOrganization(array $organizationArray)
    {
        $resource = [
            'name' => $organizationArray['name'],
        ];

        return $this->commonGroundService->createResource($resource, ['component' => 'wrc', 'type' => 'organizations']);
    }

    /**
     * This function saves a wrc/organization with the given arrays.
     *
     * @param array $ccOrganization    Array with cc/organizations data
     * @param array $organizationArray Array with wrc/organizations data
     *
     * @return array|false Returns the created organization
     */
    public function saveOrganization(array $ccOrganization, array $organizationArray)
    {
        $organization = $this->commonGroundService->getResource($ccOrganization['sourceOrganization']);
        $resource = [
            'name' => $organizationArray['name'],
        ];

        return $this->commonGroundService->updateResource($resource, ['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
    }

    /**
     * This function fetches a wrc/organization with the given ID.
     *
     * @param string $id ID of the organization
     *
     * @return array|false|mixed|string|null Returns the fetched organization
     */
    public function getOrganization(string $id)
    {
        return $result = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
    }

    /**
     * This function created a contact url for a student or aanbiederEmployee.
     *
     * @param array $input Array with students data or aanbiederEmployees data
     *
     * @throws \Exception
     *
     * @return string Returns a contact url as string
     */
    public function setContact(array $input): string
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

    /**
     * This function checks for a documents base64data.
     *
     * @param array $input Array with documents data
     *
     * @throws \Exception
     */
    public function handleDocumentProps(array $input)
    {
        $requiredProps = ['base64data', 'filename'];
        foreach ($requiredProps as $prop) {
            if (!isset($input[$prop])) {
                throw new Exception('No '.$prop.' has been given');
            }
        }
    }

    /**
     * This function creates a document with the given data.
     *
     * @param array $input Array with documents data
     *
     * @throws \Exception
     *
     * @return \App\Entity\Document Returns a Document object
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

    /**
     * This function gets the id from a document.
     *
     * @param array $input Array with documents data
     *
     * @throws \Exception
     *
     * @return false|mixed|string Returns the documents ID
     */
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
     * This function fetches the document with the given data.
     *
     * @param array $input Array with the documents data
     *
     * @throws \Exception
     *
     * @return \App\Entity\Document Returns a Document object
     */
    public function downloadDocument(array $input): Document
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
     * This function deletes a document with the given ID.
     *
     * @param array $input Array with the documents data
     *
     * @throws \Exception
     *
     * @return \App\Entity\Document|null Returns null
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
     * This function creates a Document object with the given data.
     *
     * @param array $document Array with the documents data
     *
     * @return \App\Entity\Document Returns a Document object
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
     * This function fetches a document with the given ID.
     *
     * @param string $id ID of the document
     *
     * @throws \Exception
     *
     * @return \App\Entity\Document Returns a Document object
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
     * This function fetches documents for the given contact.
     *
     * @param string|null $contact Url of the contact
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection Returns an ArrayCollection of fetched documents
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
