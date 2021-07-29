<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\LayerService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private SerializerService $serializerService;
    private UcService $ucService;

    /**
     * UserSubscriber constructor.
     *
     * @param LayerService $layerService
     * @param UcService    $ucService
     */
    public function __construct(LayerService $layerService, UcService $ucService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->ucService = $ucService;
        $this->serializerService = new SerializerService($layerService->serializer);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['user', EventPriorities::PRE_VALIDATE],
        ];
    }

    /**
     * @param ViewEvent $event
     */
    public function user(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        switch ($route) {
            case 'api_users_login_collection':
                $response = $this->login($resource);
                $this->serializerService->setResponse($response, $event, ['token']);
                break;
            case 'api_users_post_collection':
                $response = $this->createUser($resource);
                $this->serializerService->setResponse($response, $event);
                break;
            default:
                return;
        }
    }

    /**
     * handle login.
     *
     * @param User $resource
     *
     * @return Response
     */
    private function login(User $resource): object
    {
        $user = new User();
        $user->setToken($this->ucService->login($resource->getUsername(), $resource->getPassword()));
        $this->entityManager->persist($user);

        return $user;
    }

    private function createUser(User $user): User
    {
        $user = $this->ucService->createUser($user);
        return $user;
    }
}
