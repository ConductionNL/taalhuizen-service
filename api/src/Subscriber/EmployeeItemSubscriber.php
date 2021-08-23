<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Employee;
use App\Service\LayerService;
use App\Service\MrcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EmployeeItemSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private MrcService $mrcService;

    /**
     * EmployeeItemSubscriber constructor.
     *
     * @param MrcService   $mrcService
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
     * @return Employee|Response
     */
    private function getEmployee(string $id)
    {
        $employeeExists = $this->mrcService->checkIfEmployeeExists($id);
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
        $employeeExists = $this->mrcService->checkIfEmployeeExists($id);
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
}
