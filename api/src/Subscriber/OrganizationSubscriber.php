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
use phpDocumentor\Reflection\Types\Mixed_;
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
    private SerializerInterface $serializer;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;
    private UcService $ucService;
    private EDUService $eduService;
//    private MrcService $mrcService;

    /**
     * OrganizationSubscriber constructor.
     * @param LayerService $layerService
     * @param UcService $ucService
     */
    public function __construct(LayerService $layerService, UcService $ucService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->serializer = $layerService->serializer;
        $this->commonGroundService = $layerService->commonGroundService;
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
                $response = $this->createOrganization($body);
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
     * @return Organization|Response
     */
    private function createOrganization(array $body)
    {
        if (!isset($body['type'])) {
            return new Response(
                json_encode([
                    'message' => 'Please give the type of organization you want to create!',
                    'path' => 'type',
                    'data' => ['options' => 'LanguageHouse, Provider']
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }
        $organizations = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['name' => $body['name'],'type' => $body['type']])['hydra:member'];
        if (count($organizations) > 0) {
            return new Response(
                json_encode([
                    'message' => 'A '.$body['type'].' with this name already exists!',
                    'path' => 'name',
                    'data' => ['name' => $body['name']]
                ]),
                Response::HTTP_CONFLICT,
                ['content-type' => 'application/json']
            );
        }

        $organization = $this->ccService->createOrganization($body, $body['type']);
        $this->eduService->saveProgram($organization);
        $this->ucService->createUserGroups($organization, $body['type']);

        return $this->ccService->createOrganizationObject($organization);
    }
}
