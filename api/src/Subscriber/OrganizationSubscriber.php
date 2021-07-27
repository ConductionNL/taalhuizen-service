<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Organization;
use App\Entity\Taalhuis;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\UcService;
use App\Service\WRCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;
use function GuzzleHttp\json_decode;

class OrganizationSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
//    private CommonGroundService $commonGroundService;
    private SerializerInterface $serializer;
    private CCService $ccService;
    private UcService $ucService;
    private EDUService $eduService;
//    private MrcService $mrcService;

    /**
     * OrganizationSubscriber constructor.
     * @param UcService $ucService
     * @param LayerService $layerService
     */
    public function __construct(
        UcService $ucService,
        LayerService $layerService
    ) {
        $this->entityManager = $layerService->entityManager;
//        $this->commonGroundService = $layerService->commonGroundService;
        $this->serializer = $layerService->serializer;
        $this->ccService = new CCService($layerService->entityManager, $layerService->commonGroundService);
        $this->ucService = $ucService;
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
//        $this->mrcService = new MrcService($layerService, $ucService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['organization', EventPriorities::PRE_SERIALIZE],
        ];
    }

    /**
     * @param ViewEvent $event
     * @throws Exception
     */
    public function organization(ViewEvent $event)
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
            case 'api_organizations_post_collection':
                if (!isset($body['type'])) {
                    $response = new Response(
                        json_encode(['message' => 'Please give the type of organization you want to create!']),
                        Response::HTTP_BAD_REQUEST,
                        ['content-type' => $contentType]
                    );
                    break;
                }
                $response = $this->serializer->serialize(
                    $this->createOrganization($body),
                    $renderType,
                );
                break;
            default:
                return;
        }

        $this->entityManager->remove($resource);
        if ($response instanceof Response) {
            $event->setResponse($response);
            return;
        }
        $event->setResponse(new Response(
            $response,
            Response::HTTP_OK,
            ['content-type' => $contentType]
        ));
    }

    /**
     * @param array $body
     * @return Organization
     * @throws Exception
     */
    private function createOrganization(array $body): Organization
    {
        $organization = $this->ccService->createOrganization($body, $body['type']);
        $this->eduService->saveProgram($organization)['@id'];
        $this->ucService->createUserGroups($organization, $body['type']);

        return $this->ccService->createOrganizationObject($organization);
    }
}
