<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\StudentDossierEvent;
use App\Exception\BadRequestPathException;
use App\Service\EDUService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\NewRegistrationService;
use App\Service\ParticipationService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StudentDossierEventSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private EDUService $eduService;
    private UcService $ucService;
    private ErrorSerializerService $errorSerializerService;
//    private ParticipationService $participationService;

    /**
     * StudentDossierEventSubscriber constructor.
     *
     * @param EDUService   $eduService
     * @param LayerService $layerService
     */
    public function __construct(EDUService $eduService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->registrationService = new NewRegistrationService($layerService);
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->eduService = $eduService;
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);

//        $this->participationService = new ParticipationService($studentService, $layerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['studentDossierEvent', EventPriorities::PRE_VALIDATE],
        ];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     */
    public function studentDossierEvent(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        try {
            switch ($route) {
                case 'api_student_dossier_events_post_collection':
                    $response = $this->createStudentDossierEvent($resource);
                    break;
                case 'api_student_dossier_events_get_collection':
                    $response = $this->eduService->getEducationEvents($event->getRequest()->query->all());
                    break;
                case 'api_student_dossier_events_put_item':
                    $response = $this->eduService->updateEducationEvent($event->getRequest()->attributes->get('id'), $resource);
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

    /**
     * Creates a student dossier event.
     *
     * @param object $studentDossierEvent The data for the student dossier event
     *
     * @return StudentDossierEvent The resulting student dossier event
     */
    public function createStudentDossierEvent(object $studentDossierEvent): StudentDossierEvent
    {
        return $this->eduService->createEducationEvent($studentDossierEvent);
    }

    /**
     * Updates a student dossier event.
     *
     * @param string $id
     * @param object $studentDossierEvent The data for the student dossier event
     *
     * @return StudentDossierEvent The resulting student dossier event
     */
    public function updateStudentDossierEvent(string $id, object $studentDossierEvent): StudentDossierEvent
    {
        if ($studentDossierEvent instanceof StudentDossierEvent) {
            return $this->eduService->updateEducationEvent($id, $studentDossierEvent);
        } else {
            throw new Error('wrong students dossier event id');
        }
    }
}
