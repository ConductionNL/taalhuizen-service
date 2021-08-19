<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Organization;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\Collection;
use Exception;
use function GuzzleHttp\json_decode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OrganizationSubscriber implements EventSubscriberInterface
{
    private SerializerService $serializerService;
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

        // Lets limit the subscriber
        switch ($route) {
            case 'api_organizations_post_collection':
                $body = json_decode($event->getRequest()->getContent(), true);
                $response = $this->createOrganization($body);
                break;
            case 'api_organizations_get_collection':
                $response = $this->getOrganizations($event->getRequest()->query->all());
                break;
            case 'api_organizations_put_item':
                $body = json_decode($event->getRequest()->getContent(), true);
                $response = $this->updateOrganization($body, $event->getRequest()->attributes->get('id'));
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
        $uniqueName = $this->ccService->checkUniqueOrganizationName($body);
        if ($uniqueName instanceof Response) {
            return $uniqueName;
        }

        $organization = $this->ccService->createOrganization($body, $body['type']);
        $this->eduService->saveProgram($organization);
        $this->ucService->createUserGroups($organization, $body['type']);

        return $this->ccService->createOrganizationObject($organization);
    }

    /**
     * @param array  $body
     * @param string $id
     *
     * @return Organization|Response
     */
    private function updateOrganization(array $body, string $id)
    {
        $organizationExists = $this->ccService->checkIfOrganizationExists($id);
        if ($organizationExists instanceof Response) {
            return $organizationExists;
        }
        if (isset($body['name'])) {
            $body['type'] = $organizationExists['type'];
            $uniqueName = $this->ccService->checkUniqueOrganizationName($body, $id);
            if ($uniqueName instanceof Response) {
                return $uniqueName;
            }
        }

        $organizationExists['emails'] = $organizationExists['emails'][0] ?? null;
        $organizationExists['telephones'] = $organizationExists['telephones'][0] ?? null;
        $body = array_merge($organizationExists, $body);
        $organization = $this->ccService->updateOrganization($id, $body);
        $this->eduService->saveProgram($organization, true);
        $this->ucService->createUserGroups($organization, $organization['type']);

        return $this->ccService->createOrganizationObject($organization);
    }

    private function getOrganizations(array $query): Collection
    {
        return $this->ccService->getOrganizations($query);
    }
}
