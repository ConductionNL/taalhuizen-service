<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\LearningNeed;
use App\Entity\LearningNeedOutCome;
use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class DocumentService
{
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private EntityManagerInterface $entityManager;

    public function __construct(LayerService $layerService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->eavService = new EAVService($this->commonGroundService);
        $this->entityManager = $layerService->entityManager;
    }

    public function persistDocument(Document $document, array $arrays): Document
    {
        $this->entityManager->persist($document);
        $document->setId(Uuid::fromString($arrays['document']['id']));
        $this->entityManager->persist($document);

        return $document;
    }

    public function getDocuments($id = null): ArrayCollection
    {
        if ($id == null) {
            throw new BadRequestPathException('Please provide a participant ID', 'document');
        }

        try {
            $participant = $this->commonGroundService->getResource(['component' => 'edu', 'type' => 'participants', 'id' => $id]);
            $participantUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $participant['id']]);
            $documents = $this->commonGroundService->getResourceList(['component' => 'wrc', 'type' => 'documents'], ['contact' => $participantUrl]);

            $response['totalItems'] = $documents['hydra:totalItems'];
            $response['documents'] = $documents['hydra:member'];

            return new ArrayCollection($response);
        } catch (\Throwable $e) {
            throw new BadRequestPathException('Invalid request, '.$id.' is not an existing participant!', 'document');
        }
    }

    public function deleteDocument($id): Response
    {
        try {
            $document = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'documents', 'id' => $id]);
            $this->commonGroundService->deleteResource($document);
            return new Response(null, 204);
        } catch (\Throwable $e) {
            throw new BadRequestPathException('Invalid request, '.$id.' is not an existing document!', 'document');
        }
    }

    public function createDocument(Document $document): Document
    {
        $this->checkDocument($document);

        $participantUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $document->getParticipantId()]);

        $array = [
            'name' => $document->getFilename(),
            'base64' => $document->getBase64(),
            'contact' => $participantUrl
        ];

        $arrays['document'] = $this->commonGroundService->createResource($array, ['component' => 'wrc', 'type' => 'documents']);

        return $this->persistDocument($document, $arrays);
    }

    public function checkDocument(Document $document): void
    {
        if ($document->getParticipantId() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'participant id');
        }
        if ($document->getBase64() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'base64');
        }
        if ($document->getFilename() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'filename');
        }
    }

}
