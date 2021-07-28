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
use Symfony\Component\Serializer\SerializerInterface;
use function GuzzleHttp\json_decode;

class UserSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private SerializerService $serializerService;
    private UcService $ucService;

    /**
     * UserSubscriber constructor.
     * @param LayerService $layerService
     * @param UcService $ucService
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
        $body = json_decode($event->getRequest()->getContent(), true);


        // Lets limit the subscriber
        switch ($route) {
            case 'api_users_login_collection':
                $response = $this->login($resource);
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
