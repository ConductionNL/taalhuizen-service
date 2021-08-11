<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Registration;
use App\Entity\Student;
use App\Service\LayerService;
use App\Service\NewRegistrationService;
use App\Service\StudentService;
use App\Service\ParticipationService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use function GuzzleHttp\json_decode;
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
    private UcService $ucService;
//    private ParticipationService $participationService;

    /**
     * StudentSubscriber constructor.
     *
     * @param StudentService   $ucService
     * @param LayerService $layerService
     */
    public function __construct(UcService $ucService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->registrationService = new NewRegistrationService($layerService);
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->ucService = $ucService;

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

        // Lets limit the subscriber
        switch ($route) {
            case 'api_registrations_post_collection':
                $response = $this->registrationService->createRegistration($resource);
                break;
            case 'api_registrations_get_collection':
                $response = $this->getRegistrations($event->getRequest()->query->all(), $event);
                break;
            case 'api_registrations_put_item':
                $response = $this->registrationService->updateRegistration($event->getRequest()->attributes->get('id'), json_decode($event->getRequest()->getContent(), true));
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
        if($registration instanceof Registration){
            return $this->registrationService->createRegistration($registration);
        } else {
            throw new Error('wrong type');
        }
    }

    public function getRegistrations(array $query, ViewEvent $event): Collection
    {
        $token = str_replace('Bearer ', '', $event->getRequest()->headers->get('Authorization'));
        $payload = $this->ucService->validateJWTAndGetPayload($token, $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'public_key']));
//        $currentUser = $this->ucService->getUser($payload['userId']);
        $currentUser = $this->ucService->getUserArray($payload['userId']);

        if (isset($currentUser['organization']) && $this->commonGroundService->isResource($currentUser['organization'])) {
            return $this->registrationService->getRegistrations(array_merge($query, ['referredBy' => $currentUser['organization']]));
        } else {
            return $this->registrationService->getRegistrations($query);
        }
    }
}
