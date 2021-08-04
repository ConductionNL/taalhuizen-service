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
use Exception;
use function GuzzleHttp\json_decode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OrganizationItemSubscriber implements EventSubscriberInterface
{
    private SerializerService $serializerService;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;
    private UcService $ucService;
    private EDUService $eduService;
    private MrcService $mrcService;

    /**
     * OrganizationItemSubscriber constructor.
     *
     * @param LayerService $layerService
     * @param UcService    $ucService
     */
    public function __construct(LayerService $layerService, UcService $ucService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->ccService = new CCService($layerService);
        $this->ucService = $ucService;
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->mrcService = new MrcService($layerService, $ucService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['organization', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function organization(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');
        $id = $event->getRequest()->attributes->get('id');

        // Lets limit the subscriber
        switch ($route) {
            case 'api_organizations_get_item':
                $response = $this->getOrganization($id);
                break;
            case 'api_organizations_delete_item':
                $response = $this->deleteOrganization($id);
                break;
            case 'api_organizations_put_item':
                $body = json_decode($event->getRequest()->getContent(), true);
                $response = $this->updateOrganization($body, $id);
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
     * @return Organization|Response
     */
    private function getOrganization(string $id)
    {
        $organizationExists = $this->checkIfOrganizationExists($id);
        if ($organizationExists instanceof Response) {
            return $organizationExists;
        }

        return $this->ccService->getOrganization($id);
    }

    /**
     * @param string $id
     *
     * @throws Exception
     *
     * @return Response
     */
    private function deleteOrganization(string $id): Response
    {
        $organizationExists = $this->checkIfOrganizationExists($id);
        if ($organizationExists instanceof Response) {
            return $organizationExists;
        }

        try {
            //delete userGroups
            $this->ucService->deleteUserGroups($id);

            //delete employees
            $this->mrcService->deleteEmployees($id);

            //delete participants, TODO: this should be done with the studentService (new) deleteStudent(s) function!
            //(because of learningNeeds and other EAV objects that will not be delete this way:)
            $programId = $this->eduService->deleteParticipants($id);

            $this->ccService->deleteOrganization($id, $programId);

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
     * @param array  $body
     * @param string $id
     *
     * @return Organization|Response
     */
    private function updateOrganization(array $body, string $id)
    {
        $organizationExists = $this->checkIfOrganizationExists($id);
        if ($organizationExists instanceof Response) {
            return $organizationExists;
        }
        $body['type'] = $organizationExists['type'];
        $uniqueName = $this->ccService->checkUniqueOrganizationName($body, $id);
        if ($uniqueName instanceof Response) {
            return $uniqueName;
        }

        $organization = $this->ccService->updateOrganization($id, $body);
        $this->eduService->saveProgram($organization, true);
        $this->ucService->createUserGroups($organization, $organization['type']);

        return $this->ccService->createOrganizationObject($organization);
    }

    /**
     * @param string $id
     *
     * @return array|false|mixed|string|Response|null
     */
    private function checkIfOrganizationExists(string $id)
    {
        $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $id]);
        if (!$this->commonGroundService->isResource($organizationUrl)) {
            return new Response(
                json_encode([
                    'message' => 'This organization does not exist!',
                    'path'    => '',
                    'data'    => ['organization' => $organizationUrl],
                ]),
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'application/json']
            );
        }

        return $this->commonGroundService->getResource($organizationUrl);
    }
}
