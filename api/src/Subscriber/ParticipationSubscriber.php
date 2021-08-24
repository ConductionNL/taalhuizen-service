<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Participation;
use App\Exception\BadRequestPathException;
use App\Service\EAVService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\NewParticipationService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ParticipationSubscriber implements EventSubscriberInterface
{
    private EAVService $eavService;
    private NewParticipationService $participationService;
    private SerializerService $serializerService;
    private ErrorSerializerService $errorSerializerService;

    public function __construct(EAVService $eavService, LayerService $layerService)
    {
        $this->eavService = $eavService;
        $this->participationService = new NewParticipationService($layerService);
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['participation', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function participation(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        try {
            switch ($route) {
                case 'api_participations_post_collection':
                    $response = $this->participationService->createParticipation($resource);
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
