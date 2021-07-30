<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Organization;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use function GuzzleHttp\json_decode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OrganizationSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private SerializerService $serializerService;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;
    private UcService $ucService;
    private EDUService $eduService;
//    private MrcService $mrcService;

    /**
     * OrganizationSubscriber constructor.
     *
     * @param LayerService $layerService
     * @param UcService    $ucService
     */
    public function __construct(LayerService $layerService, UcService $ucService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->ccService = new CCService($layerService);
        $this->ucService = $ucService;
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
        $this->serializerService = new SerializerService($layerService->serializer);
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
     *
     * @throws Exception
     */
    public function organization(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();
        $body = json_decode($event->getRequest()->getContent(), true);

        var_dump($route);
        var_dump($event->getRequest()->get('id')); die();

        // Lets limit the subscriber
        switch ($route) {
            case 'api_organizations_post_collection':
                $response = $this->createOrganization($body);
                break;
            case 'api_organizations_get_item':
                $response = $this->getOrganization($event->getRequest()->get('id'));
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
     * @return Organization|Response
     */
    private function createOrganization(array $body)
    {
        if (!isset($body['type'])) {
            return new Response(
                json_encode([
                    'message' => 'Please give the type of organization you want to create!',
                    'path'    => 'type',
                    'data'    => ['options' => 'LanguageHouse, Provider'],
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }
        $organizations = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['name' => $body['name'], 'type' => $body['type']])['hydra:member'];
        if (count($organizations) > 0) {
            return new Response(
                json_encode([
                    'message' => 'A '.$body['type'].' with this name already exists!',
                    'path'    => 'name',
                    'data'    => ['name' => $body['name']],
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

    /**
     * @param string $id
     * @return Organization
     */
    private function getOrganization(string $id): Organization
    {
        return $this->ccService->getOrganization($id);
    }
}
