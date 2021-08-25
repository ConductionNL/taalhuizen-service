<?php

namespace App\Service;

use App\Entity\Document;
use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class DocumentService
{
    private CommonGroundService $commonGroundService;
    private EntityManagerInterface $entityManager;

    public function __construct(LayerService $layerService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
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

    public function getDocument($id = null): arrayCollection
    {
        if ($id == null) {
            throw new BadRequestPathException('Please provide a document ID', 'document');
        }

        try {
            $document = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'documents', 'id' => $id]);

            $response['filename'] = $document['name'];
            $response['base64'] = $document['base64'];

            return new ArrayCollection($response);
        } catch (\Throwable $e) {
            throw new BadRequestPathException('Cant retrieve object, '.$id.' is not an existing document!', 'document');
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
        $this->checkExtensionAndMime($document);
        $this->determineFileSizeFromBase64String($document->getBase64());

        $participantUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $document->getParticipantId()]);

        $array = [
            'name'    => $document->getFilename(),
            'base64'  => $document->getBase64(),
            'contact' => $participantUrl,
        ];

        $arrays['document'] = $this->commonGroundService->createResource($array, ['component' => 'wrc', 'type' => 'documents']);

        return $this->persistDocument($document, $arrays);
    }

    public function checkExtensionAndMime(Document $document): void
    {
        $combinations = [
            'txt'  => 'text/plain',
            'pdf'  => 'application/pdf',
            'svg'  => 'image/svg+xml',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'  => 'application/msword',
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
        ];

        $mime = $this->retrieveMimeTypeFromBase64String($document->getBase64());
        $extension = $this->retrieveFileExtensionFromFilename($document->getFilename());

        if ($combinations[$extension] !== $mime) {
            throw new BadRequestPathException("Mime type and file extension don't match", 'base64');
        }
    }

    public function determineFileSizeFromBase64String(string $base64): void
    {
        $size = $this->getBase64Size($base64);

        if ($size > .5) {
            throw new BadRequestPathException('File size exceeds the 500kb limit', 'base64');
        }
    }

    public function getBase64Size($base64)
    { //return memory size in B, KB, MB
        $size_in_bytes = (int) (strlen(rtrim($base64, '=')) * 3 / 4);
        $size_in_kb = $size_in_bytes / 1024;
        $size_in_mb = $size_in_kb / 1024;

        return $size_in_mb;
    }

    public function retrieveMimeTypeFromBase64String(string $base64)
    {
        $allowedMimeTypes = [
            'text/plain',
            'application/pdf',
            'image/svg+xml',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
        if (preg_match('/data:(.*?);/', $base64, $match) == 1) {
            if (!in_array($match[1], $allowedMimeTypes)) {
                throw new BadRequestPathException('Mime type must be one of the following: '.implode(', ', $allowedMimeTypes), 'base64');
            }

            return $match[1];
        } else {
            throw new BadRequestPathException('Base64 has invalid MIME type definition', 'base64');
        }
    }

    public function retrieveFileExtensionFromFilename(string $filename)
    {
        $exploded = explode('.', $filename);
        $allowedExtensions = ['txt', 'pdf', 'svg', 'docx', 'doc', 'jpeg', 'jpg', 'png', 'gif'];
        if (count($exploded) == 1) {
            throw new BadRequestPathException('No extension found in filename', 'base64');
        } else {
            if (!in_array(end($exploded), $allowedExtensions)) {
                throw new BadRequestPathException('Extension must be one of the following: '.implode(', ', $allowedExtensions), 'base64');
            }

            return end($exploded);
        }
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
