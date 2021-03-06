<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Organization;
use App\Exception\BadRequestPathException;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Exception;
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
    private ErrorSerializerService $errorSerializerService;

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
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);
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
        try {
            switch ($route) {
                case 'api_organizations_get_item':
                    $response = $this->getOrganization($id);
                    break;
                case 'api_organizations_delete_item':
                    $response = $this->deleteOrganization($id);
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
     * @param string $id
     *
     * @return Organization|Response
     */
    private function getOrganization(string $id)
    {
        $organizationExists = $this->ccService->checkIfOrganizationExists($id);
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
        $organizationExists = $this->ccService->checkIfOrganizationExists($id);
        if ($organizationExists instanceof Response) {
            return $organizationExists;
        }
        //delete userGroups
        $this->ucService->deleteUserGroups($id);

        //delete employees
        $this->mrcService->deleteEmployees($id);

        //delete participants, TODO: this should be done with the studentService (new) deleteStudent(s) function!
        //(because of learningNeeds and other EAV objects that will not be delete this way:)
        $programId = $this->eduService->deleteParticipants($id);

        $this->ccService->deleteOrganization($id, $programId);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
