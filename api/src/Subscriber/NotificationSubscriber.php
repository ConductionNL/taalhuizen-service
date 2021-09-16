<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Organization;
use App\Exception\BadRequestPathException;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\NotificationService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\Collection;
use Exception;
use function GuzzleHttp\json_decode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class NotificationSubscriber implements EventSubscriberInterface
{

    private NotificationService $notificationService;

    /**
     * NotificationSubscriber constructor.
     *
     * @param LayerService $layerService
     */
    public function __construct(LayerService $layerService)
    {
        $this->notificationService = new NotificationService($layerService->commonGroundService, $layerService->parameterBag);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['notification', EventPriorities::PRE_VALIDATE],
        ];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     */
    public function notification(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');

        // Lets limit the subscriber
        if ($route != 'api_notifications_post_collection') {
            return;
        }

        $service = $event->getRequest()->attributes->get("service");
        $type = $event->getRequest()->attributes->get("type");
        $this->notificationService->processNotification($service, $type);
    }
}
