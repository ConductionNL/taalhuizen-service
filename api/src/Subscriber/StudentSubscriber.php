<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Student;
use App\Service\LayerService;
use App\Service\StudentService;
use App\Service\ParticipationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use function GuzzleHttp\json_decode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StudentSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private StudentService $studentService;
//    private ParticipationService $participationService;

    /**
     * StudentSubscriber constructor.
     *
     * @param StudentService   $studentService
     * @param LayerService $layerService
     */
    public function __construct(StudentService $studentService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->studentService = $studentService;
        $this->serializerService = new SerializerService($layerService->serializer);
//        $this->participationService = new ParticipationService($studentService, $layerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['student', EventPriorities::PRE_SERIALIZE],
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
        $body = json_decode($event->getRequest()->getContent(), true);



        // Lets limit the subscriber
        switch ($route) {
            case 'api_students_post_collection':
                $response = $this->createStudent($body);
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

    /**
     * @param array $body
     *
     * @throws Exception
     *
     * @return Student
     */
    private function createStudent(array $body): Student
    {
//        if (!isset($body['person']['emails']['email'])) {
//            return new Response(
//                json_encode([
//                    'message' => 'The person of this student must contain an email!',
//                    'path'    => 'person.emails.email',
//                ]),
//                Response::HTTP_BAD_REQUEST,
//                ['content-type' => 'application/json']
//            );
//        }
//        $uniqueEmail = $this->studentService->checkUniqueStudentEmail($body);
//        if ($uniqueEmail instanceof Response) {
//            return $uniqueEmail;
//        }

        return $this->studentService->createStudent($body);
    }
}
