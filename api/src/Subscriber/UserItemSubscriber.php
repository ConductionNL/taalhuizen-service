<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Organization;
use App\Entity\User;
use App\Service\CCService;
use App\Service\LayerService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserItemSubscriber implements EventSubscriberInterface
{
    private SerializerService $serializerService;
    private UcService $ucService;

    /**
     * UserItemSubscriber constructor.
     *
     * @param LayerService $layerService
     * @param UcService    $ucService
     */
    public function __construct(LayerService $layerService, UcService $ucService)
    {
        $this->ucService = $ucService;
        $this->serializerService = new SerializerService($layerService->serializer);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['user', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     * @throws Exception
     */
    public function user(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');

        // Lets limit the subscriber
        switch ($route) {
            case 'api_users_get_item':
                $response = $this->getUser($event->getRequest()->attributes->get('id'));
                break;
            case 'api_users_delete_item':
                $response = $this->deleteUser($event->getRequest()->attributes->get('id'));
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
     * @return Response
     */
    private function deleteUser(string $id): Response
    {
        $this->ucService->deleteUser($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param string $id
     *
     * @return User
     * @throws Exception
     */
    private function getUser(string $id): User
    {
        return $this->ucService->getUser($id);
    }
}
