<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Employee;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use function GuzzleHttp\json_decode;

class EmployeeItemSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private MrcService $mrcService;

    /**
     * EmployeeItemSubscriber constructor.
     *
     * @param MrcService $mrcService
     * @param LayerService $layerService
     */
    public function __construct(MrcService $mrcService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->mrcService = $mrcService;
        $this->serializerService = new SerializerService($layerService->serializer);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['employee', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function employee(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');
        $id = $event->getRequest()->attributes->get('id');

        // Lets limit the subscriber
        switch ($route) {
            case 'api_employees_get_item':
                $response = $this->getEmployee($id);
                break;
            case 'api_employees_delete_item':
                $response = $this->deleteEmployee($id);
                break;
            case 'api_employees_put_item':
                $body = json_decode($event->getRequest()->getContent(), true);
                $response = $this->updateEmployee($body, $id);
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
     * @return Employee|Response
     * @throws Exception
     */
    private function getEmployee(string $id)
    {
        $employeeExists = $this->checkIfEmployeeExists($id);
        if ($employeeExists instanceof Response) {
            return $employeeExists;
        }

        return $this->mrcService->getEmployee($id);
    }

    /**
     * @param string $id
     *
     * @throws Exception
     *
     * @return Response
     */
    private function deleteEmployee(string $id): Response
    {
        $employeeExists = $this->checkIfEmployeeExists($id);
        if ($employeeExists instanceof Response) {
            return $employeeExists;
        }

        try {
            $this->mrcService->deleteEmployee($id);

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
     * @param array $body
     * @param string $id
     * @return Employee|Response
     * @throws Exception
     */
    private function updateEmployee(array $body, string $id)
    {
        $employeeExists = $this->checkIfEmployeeExists($id);
        if ($employeeExists instanceof Response) {
            return $employeeExists;
        }
        if (!isset($body['userId'])) {
            return new Response(
                json_encode([
                    'message' => 'Please give the userId of the employee you want to update!',
                    'path'    => 'userId',
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }
        $uniqueEmail = $this->mrcService->checkUniqueEmployeeEmail($body, $body['userId']);
        if ($uniqueEmail instanceof Response) {
            return $uniqueEmail;
        }

        return $this->mrcService->updateEmployee($id, $body);
    }

    /**
     * @param string $id
     * @return Response|null
     */
    private function checkIfEmployeeExists(string $id): ?Response
    {
        $employeeUrl = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]);
        if (!$this->commonGroundService->isResource($employeeUrl)) {
            return new Response(
                json_encode([
                    'message' => 'This employee does not exist!',
                    'path'    => '',
                    'data'    => ['employee' => $employeeUrl],
                ]),
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'application/json']
            );
        }
        return null;
    }
}
