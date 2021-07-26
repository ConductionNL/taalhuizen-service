<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\UcService;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private UcService $ucService;

    public function __construct(EntityManagerInterface $entityManager, UcService $ucService)
    {
        $this->entityManager = $entityManager;
        $this->ucService = $ucService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['user', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function user(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        if ($route != 'api_users_login_collection') {
            return;
        }

        //handle login
        if ($route == 'api_users_login_collection' && $resource instanceof User) {
            $result = [
                "token" => $this->ucService->login($resource->getUsername(), $resource->getPassword())
            ];
            $this->entityManager->remove($resource);
            $this->entityManager->flush();
        }

        // Create the response
        $response = new Response(
            json_encode($result),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
        $event->setResponse($response);
    }
}
