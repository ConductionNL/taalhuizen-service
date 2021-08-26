<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Exception\BadRequestPathException;
use App\Service\DocumentService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DocumentItemSubscriber implements EventSubscriberInterface
{
    private SerializerService $serializerService;
    private DocumentService $documentService;
    private ErrorSerializerService $errorSerializerService;

    /**
     * UserItemSubscriber constructor.
     *
     * @param LayerService $layerService
     */
    public function __construct(LayerService $layerService)
    {
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->documentService = new DocumentService($layerService);
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['document', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function document(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');

        try {
            switch ($route) {
                case 'api_documents_delete_item':
                    $response = $this->documentService->deleteDocument($event->getRequest()->attributes->get('id'));
                    break;
                case 'api_documents_get_item':
                    $response = $this->documentService->getDocument($event->getRequest()->attributes->get('id'));
                    break;
                default:
                    return;
            }
            if ($response instanceof Response) {
                $event->setResponse($response);

                return;
            }
            $this->serializerService->setResponse($response, $event);
        } catch (BadRequestPathException $exception) {
            $this->errorSerializerService->serialize($exception, $event);
        }
    }
}
