<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Registration;
use App\Entity\StudentDossierEvent;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\NewRegistrationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StudentDossierEventItemSubscriber implements EventSubscriberInterface
{
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private EDUService $eduService;

    /**
     * UserItemSubscriber constructor.
     *
     * @param LayerService           $layerService
     * @param EDUService $eduService
     */
    public function __construct(LayerService $layerService, EDUService $eduService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->eduService = $eduService;
        $this->serializerService = new SerializerService($layerService->serializer);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['studentDossierEvents', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function studentDossierEvents(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');

        // Lets limit the subscriber
        switch ($route) {
            case 'api_registrations_get_item':
                $response = $this->eduService->getEducationEvent($event->getRequest()->attributes->get('id'));
                break;
            case 'api_registrations_delete_item':
                $response = $this->eduService->deleteEducationEvent($event->getRequest()->attributes->get('id'));
                break;
            default:
                return;
        }

        if ($response instanceof Response) {
            $event->setResponse($response);

            return;
        }
        $this->serializerService->setResponse($response, $event);
    }

}
