<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Student;
use App\Service\LayerService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use function GuzzleHttp\json_decode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class StudentItemSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private StudentService $studentService;

    /**
     * StudentItemSubscriber constructor.
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
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['student', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function student(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');
        $id = $event->getRequest()->attributes->get('id');

        // Lets limit the subscriber
        switch ($route) {
            case 'api_students_get_item':
                $response = $this->getStudent($id);
                break;
            case 'api_students_delete_item':
                $response = $this->deleteStudent($id);
                break;
            case 'api_students_put_item':
                $body = json_decode($event->getRequest()->getContent(), true);
                $response = $this->updateStudent($body, $id);
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
     * @param string $id
     *
     * @throws Exception
     *
     * @return Student|Response
     */
    private function getStudent(string $id)
    {
        $studentExists = $this->checkIfStudentExists($id);
        if ($studentExists instanceof Response) {
            return $studentExists;
        }

        return $this->studentService->getStudent($id);
    }

    /**
     * @param string $id
     *
     * @throws Exception
     *
     * @return Response
     */
    private function deleteStudent(string $id): Response
    {
        $studentExists = $this->checkIfStudentExists($id);
        if ($studentExists instanceof Response) {
            return $studentExists;
        }

        try {
            $this->studentService->deleteStudent($id);

            return new Response(null, Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            return new Response(
                json_encode([
                    'message' => 'Something went wrong!',
                    'path'    => '',
                    'data'    => ['Exception' => $exception->getMessage()],
                ]),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['content-type' => 'application/json']
            );
        }
    }

    /**
     * @param array  $body
     * @param string $id
     *
     * @throws Exception
     *
     * @return Student|Response|object
     */
    private function updateStudent(array $body, string $id)
    {
        $studentExists = $this->checkIfStudentExists($id);
        if ($studentExists instanceof Response) {
            return $studentExists;
        }
//        if (!isset($body['userId'])) {
//            return new Response(
//                json_encode([
//                    'message' => 'Please give the userId of the student you want to update!',
//                    'path'    => 'userId',
//                ]),
//                Response::HTTP_BAD_REQUEST,
//                ['content-type' => 'application/json']
//            );
//        }
//        $uniqueEmail = $this->studentService->checkUniqueStudentEmail($body, $body['userId']);
//        if ($uniqueEmail instanceof Response) {
//            return $uniqueEmail;
//        }
//
        return $this->studentService->updateStudent($body, $id);
    }

    /**
     * @param string $id
     *
     * @return Response|null
     */
    private function checkIfStudentExists(string $id): ?Response
    {
        $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id]);
        if (!$this->commonGroundService->isResource($studentUrl)) {
            return new Response(
                json_encode([
                    'message' => 'This student does not exist!',
                    'path'    => '',
                    'data'    => ['student' => $studentUrl],
                ]),
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'application/json']
            );
        }

        return null;
    }
}
