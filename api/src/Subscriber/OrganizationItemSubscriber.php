<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Organization;
use App\Service\CCService;
use App\Service\LayerService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OrganizationItemSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private SerializerService $serializerService;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;

    /**
     * OrganizationItemSubscriber constructor.
     *
     * @param LayerService $layerService
     * @param UcService    $ucService
     */
    public function __construct(LayerService $layerService, UcService $ucService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->ccService = new CCService($layerService);
        $this->serializerService = new SerializerService($layerService->serializer);
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

        // Lets limit the subscriber
        switch ($route) {
            case 'api_organizations_get_item':
                $response = $this->getOrganization($event->getRequest()->get('id'));
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
     * @return Organization
     */
    private function getOrganization(string $id): Organization
    {
        return $this->ccService->getOrganization($id);
    }
}
