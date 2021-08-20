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
            KernelEvents::VIEW => ['employee', EventPriorities::PRE_SERIALIZE],
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
                default:
                    return;
                case 'api_employees_get_collection':
                    $response = $this->getEmployees($event->getRequest()->query->all());
                    break;
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
     * @param array $query
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmployees(array $query): Collection
    {
        if (!isset($query['languageHouseId']) || $this->commonGroundService->isResource($this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $query['languageHouseId']]) == false)) {
            throw new BadRequestPathException('Missing languageHouseId or languageHouse does not exist.', 'languageHouseId');
        }

        return $this->mrcService->getEmployees($query['languageHouseId']);
    }
}
