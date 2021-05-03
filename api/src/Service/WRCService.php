<?php


namespace App\Service;

use App\Entity\Document;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\DBAL\Exception\ConstraintViolationException;
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

    public function saveOrganization(array $body, $contact = null)
    {
        if (isset($body['address'])) unset($body['address']);
        if (isset($body['email'])) unset($body['email']);
        if (isset($body['phoneNumber'])) unset($body['phoneNumber']);
        if (isset($contact)) $body['contact'] = $contact;
        $result = $this->commonGroundService->saveResource($body, ['component' => 'wrc', 'type' => 'organizations']);

        return $result;
    }

    public function getOrganization($id)
    {
        return $result = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
    }

    /**
     * @throws Exception
     */
    public function createDocument($input): Document
    {
        $requiredProps = ['base64data', 'filename'];
        foreach ($requiredProps as $prop) {
            if (!isset($input[$prop])) {
                throw new Exception('No ' . $prop . ' has been given');
            }
        }
        if (isset($input['studentId']) && isset($input['aanbiederEmployeetId'])) {
            throw new Exception('Both studentId and aanbiederEmployeetId are given, please give one type of id');
        }
        if (isset($input['studentId'])) {
            $id = $input['studentId'];
        } elseif (isset($input['aanbiederEmployeetId'])) {
            $id = $input['aanbiederEmployeeId'];
        } else {
            throw new Exception('No studentId or aanbiederEmployeetId given');
        }
        if (strpos($id, '/') !== false) {
            $idArray = explode('/', $id);
            $id = end($idArray);
        }

        $contact = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $id]);

        if ($this->commonGroundService->isResource($contact)) {
            $document['name'] = $input['filename'];
            $document['base64'] = $input['base64data'];
            $document['contact'] = $contact;
        } else {
            throw new Exception('The person (cc/person) of the given id does not exist!');
        }

        try {
            $document = $this->commonGroundService->saveResource($document, ['component' => 'wrc', 'type' => 'documents']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $documentObject = new Document();
        $documentObject->setId(Uuid::getFactory()->fromString($document['id']));
        $documentObject->setFilename($document['name']);
        $documentObject->setBase64data($document['base64']);
        $documentObject->setDateCreated($document['dateCreated']);

        return $documentObject;
    }

    /**
     * @throws Exception
     */
    public function downloadDocument($input): Document
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

        try {
            $document = $this->commonGroundService->getResource($this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'documents', 'id' => $id]));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $documentObject = new Document();
        $documentObject->setId(Uuid::getFactory()->fromString($document['id']));
        $documentObject->setBase64data($document['base64']);

        return $documentObject;

    }

    /**
     * @throws Exception
     */
    public function removeDocument($input): ?Document
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
        try {
            $this->commonGroundService->deleteResource(null, ['component'=>'wrc', 'type' => 'documents', 'id' => $id]);
            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


}
