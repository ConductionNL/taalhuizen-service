<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Student;
use App\Exception\BadRequestPathException;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\ParticipationService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StudentSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private MrcService $mrcService;
    private SerializerService $serializerService;
    private StudentService $studentService;
    private ErrorSerializerService $errorSerializerService;
//    private ParticipationService $participationService;

    /**
     * StudentSubscriber constructor.
     *
     * @param StudentService $studentService
     * @param LayerService   $layerService
     */
    public function __construct(StudentService $studentService, MrcService $mrcService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->studentService = $studentService;
        $this->mrcService = $mrcService;
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);
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
        try {
            switch ($route) {
                case 'api_students_post_collection':
                    $response = $this->createStudent($resource);
                    break;
                case 'api_students_get_collection':
                    $response = $this->getStudents($event->getRequest()->query->all());
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

    /**
     * @param Student $student
     *
     * @throws Exception
     *
     * @return Student|Response|object
     */
    private function createStudent(Student $student): Student
    {
        if (!isset($body['person']['emails']['email'])) {
            return new Response(
                json_encode([
                    'message' => 'The person of this student must contain an email!',
                    'path'    => 'person.emails.email',
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }
        $uniqueEmail = $this->mrcService->checkUniqueEmployeeEmail($body);
        if ($uniqueEmail instanceof Response) {
            return $uniqueEmail;
        }

        return $this->studentService->createStudent($body);
    }

    private function getStudents(array $query): ArrayCollection
    {
        return $this->studentService->newGetStudents($query);
    }
}
