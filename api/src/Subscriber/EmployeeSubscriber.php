<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Employee;
use App\Exception\BadRequestPathException;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\ParticipationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use function GuzzleHttp\json_decode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EmployeeSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private MrcService $mrcService;
    private ErrorSerializerService $errorSerializerService;
//    private ParticipationService $participationService;

    /**
     * EmployeeSubscriber constructor.
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
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);
//        $this->participationService = new ParticipationService($mrcService, $layerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['employee', EventPriorities::PRE_VALIDATE],
        ];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     */
    public function employee(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        try {
            switch ($route) {
                case 'api_employees_post_collection':
                    $body = json_decode($event->getRequest()->getContent(), true);
                    $response = $this->createEmployee($body);
                    break;
                case 'api_employees_get_collection':
                    $response = $this->getEmployees($event->getRequest()->query->all());
                    break;
                case 'api_employees_put_item':
                    $body = json_decode($event->getRequest()->getContent(), true);
                    $response = $this->updateEmployee($body, $event->getRequest()->attributes->get('id'));
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
     * @param array $body
     *
     * @return Employee|Response
     * @throws Exception
     *
     */
    private function createEmployee(array $body)
    {
        if (!isset($body['person']['emails']['email'])) {
            return new Response(
                json_encode([
                    'message' => 'The person of this employee must contain an email!',
                    'path' => 'person.emails.email',
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }
        $uniqueEmail = $this->mrcService->checkUniqueEmployeeEmail($body);
        if ($uniqueEmail instanceof Response) {
            return $uniqueEmail;
        }

        return $this->mrcService->createEmployee($body);
    }

    /**
     * @param array $body
     * @param string $id
     *
     * @return Employee|Response
     * @throws Exception
     *
     */
    private function updateEmployee(array $body, string $id)
    {
        $employeeExists = $this->mrcService->checkIfEmployeeExists($id);
        if ($employeeExists instanceof Response) {
            return $employeeExists;
        }
        if (!isset($body['userId'])) {
            return new Response(
                json_encode([
                    'message' => 'Please give the userId of the employee you want to update!',
                    'path' => 'userId',
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
     * @param array $query
     *
     * @return Collection|Response
     * @throws Exception
     *
     */
    private function getEmployees(array $query)
    {
        if (isset($query['organizationId'])) {
            $query['organization'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $query['organizationId']]);
            if (!$this->commonGroundService->isResource($query['organization'])) {
                return new Response(
                    json_encode([
                        'message' => 'Organization does not exist!',
                        'path' => 'organizationId',
                        'data' => ['organization' => $query['organization']],
                    ]),
                    Response::HTTP_BAD_REQUEST,
                    ['content-type' => 'application/json']
                );
            }
            unset($query['organizationId']);
        }

        return $this->mrcService->getEmployees($query);
    }
}
