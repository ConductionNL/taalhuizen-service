<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Employee;
use App\Service\LayerService;
use App\Service\MrcService;
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
use Symfony\Component\Serializer\SerializerInterface;

class EmployeeSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private MrcService $mrcService;
//    private ParticipationService $participationService;

    /**
     * EmployeeSubscriber constructor.
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
        $body = json_decode($event->getRequest()->getContent(), true);

        // Lets limit the subscriber
        switch ($route) {
            case 'api_employees_post_collection':
                $response = $this->createEmployee($body);
                break;
            default:
                return;
        }

        $this->entityManager->remove($resource);
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
     * @return Employee|Response
     */
    private function createEmployee(array $body)
    {
        if (!isset($body['person']['emails']['email'])) {
            return new Response(
                json_encode([
                    'message' => 'The person of this employee must contain an email!',
                    'path'    => 'person.emails.email',
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => str_replace('+','%2B', $body['person']['emails']['email'])])['hydra:member'];
        if (count($users) > 0) {
            return new Response(
                json_encode([
                    'message' => 'A user with this email already exists!',
                    'path'    => 'person.emails.email',
                    'data'    => ['email' => $body['person']['emails']['email']],
                ]),
                Response::HTTP_CONFLICT,
                ['content-type' => 'application/json']
            );
        }

        return $this->mrcService->createEmployee($body);
    }
}
