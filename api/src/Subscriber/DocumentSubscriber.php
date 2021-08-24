<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\LearningNeed;
use App\Exception\BadRequestPathException;
use App\Service\DocumentService;
use App\Service\EAVService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\NewLearningNeedService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DocumentSubscriber implements EventSubscriberInterface
{
    private EAVService $eavService;
    private DocumentService $documentService;
    private SerializerService $serializerService;
    private ErrorSerializerService $errorSerializerService;

    public function __construct(EAVService $eavService, LayerService $layerService)
    {
        $this->eavService = $eavService;
        $this->documentService = new DocumentService($layerService);
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['document', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function document(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        try {
            switch ($route) {
                case 'api_documents_post_collection':
                    $response = $this->documentService->createDocument($resource);
                    break;
                case 'api_documents_get_collection':
                    $response = $this->documentService->getDocuments($event->getRequest()->query->get('participantId'));
                    break;
                default:
                    return;
            }

            if ($response instanceof Response) {
                $event->setResponse($response);
            }
            $this->serializerService->setResponse($response, $event);
        } catch (BadRequestPathException $exception) {
            $this->errorSerializerService->serialize($exception, $event);
        }
    }

}
