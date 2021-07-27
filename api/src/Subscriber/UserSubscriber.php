<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\UcService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private UcService $ucService;

    /**
     * UserSubscriber constructor.
     * @param EntityManagerInterface $entityManager
     * @param UcService $ucService
     */
    public function __construct(EntityManagerInterface $entityManager, UcService $ucService)
    {
        $this->entityManager = $entityManager;
        $this->ucService = $ucService;
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
                $event->setResponse($this->login($resource));
                break;
            default:
                return;
        }
        $this->entityManager->remove($resource);
        $this->entityManager->flush();
    }

    /**
     * handle login
     *
     * @param User $resource
     * @return Response
     */
    private function login(User $resource): Response
    {
        $result = [
            "token" => $this->ucService->login($resource->getUsername(), $resource->getPassword())
        ];

        return new Response(
            json_encode($result),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
