<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Registration;
use App\Service\LayerService;
use App\Service\NewRegistrationService;
use App\Service\ParticipationService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RegistrationSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private NewRegistrationService $registrationService;
//    private ParticipationService $participationService;

    /**
     * StudentSubscriber constructor.
     *
     * @param StudentService $registrationService
     * @param LayerService   $layerService
     */
    public function __construct(NewRegistrationService $registrationService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->registrationService = $registrationService;
        $this->serializerService = new SerializerService($layerService->serializer);
//        $this->participationService = new ParticipationService($studentService, $layerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['student', EventPriorities::PRE_VALIDATE],
        ];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     */
    public function student(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        var_dump($route);
        var_dump($event->getRequest()->attributes->all());
        // Lets limit the subscriber
        switch ($route) {
            case 'api_registrations_post_collection':
                $response = $this->registrationService->createRegistration($resource);
                break;
            case 'api_registrations_get_item':
                $response = $this->registrationService->getRegistration($event->getRequest()->attributes->get('id'));
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

    public function createRegistration(object $registration): Registration
    {
        if ($registration instanceof Registration) {
            return $this->registrationService->createRegistration($registration);
        } else {
            throw new Error('wrong type');
        }
    }
}
