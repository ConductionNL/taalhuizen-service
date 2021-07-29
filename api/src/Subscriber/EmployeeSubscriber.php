<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Employee;
use App\Entity\Organization;
use App\Entity\Taalhuis;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\ParticipationService;
use App\Service\UcService;
use App\Service\WRCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\Mixed_;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;
use function GuzzleHttp\json_decode;

class EmployeeSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private CommonGroundService $commonGroundService;
    private MrcService $mrcService;
//    private ParticipationService $participationService;

    /**
     * EmployeeSubscriber constructor.
     * @param MrcService $mrcService
     * @param LayerService $layerService
     */
    public function __construct(MrcService $mrcService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->serializer = $layerService->serializer;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->mrcService = $mrcService;
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
     * @throws Exception
     */
    public function employee(ViewEvent $event)
    {
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();
        $body = json_decode($event->getRequest()->getContent(), true);

        if (!$contentType) {
            $contentType = $event->getRequest()->headers->get('Accept');
        }

        switch ($contentType) {
            case 'application/json':
                $renderType = 'json';
                break;
            case 'application/ld+json':
                $renderType = 'jsonld';
                break;
            case 'application/hal+json':
                $renderType = 'jsonhal';
                break;
            default:
                $contentType = 'application/ld+json';
                $renderType = 'jsonld';
        }

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
        $response = $this->serializer->serialize(
            $response,
            $renderType,
        );
        $event->setResponse(new Response(
            $response,
            Response::HTTP_OK,
            ['content-type' => $contentType]
        ));
    }

    /**
     * @param array $body
     * @return Employee|Response
     * @throws Exception
     */
    private function createEmployee(array $body)
    {
        if (!isset($body['person']['emails']['email'])) {
            return new Response(
                json_encode([
                    'message' => 'The person of this employee must contain an email!',
                    'path' => 'person.emails.email'
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $body['person']['emails']['email']])['hydra:member'];
        if (count($users) > 0) {
            return new Response(
                json_encode([
                    'message' => 'A user with this email already exists!',
                    'path' => 'person.emails.email',
                    'data' => ['email' => $body['person']['emails']['email']]
                ]),
                Response::HTTP_CONFLICT,
                ['content-type' => 'application/json']
            );
        }
        return $this->mrcService->createEmployee($body);
    }
}
