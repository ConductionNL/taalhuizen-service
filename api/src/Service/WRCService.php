<?php


namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
    public function createDocument($input): array
    {
        if (!isset($input['studentId']) && !isset($input['aanbiederEmployeeId'])) {
            throw new Exception('No studentId or aanbiederEmployeeId given');
        }
        $requiredProps = ['base64data', 'filename'];
        foreach ($requiredProps as $prop) {
            if (!isset($input[$prop])) {
                throw new Exception('No ' . $prop . ' has been given');
            }
        }
        if (isset($input['studentId'])) {
            $id = $input['studentId'];
        } elseif (isset($input['aanbiederEmployeeId'])) {
            $id = $input['aanbiederEmployeeId'];
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

        $returnedDocument['id'] = $document['id'];
        $returnedDocument['filename'] = $document['name'];
        $returnedDocument['dateCreated'] = $document['dateCreated'];

        return $returnedDocument;
    }

    /**
     * @throws Exception
     */
    public function downloadDocument()
    {

        return '';

    }

    /**
     * @throws Exception
     */
    public function deleteDocument()
    {

        return '';
    }


}
