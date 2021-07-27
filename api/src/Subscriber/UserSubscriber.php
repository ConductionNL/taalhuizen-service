<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\LayerService;
use App\Service\UcService;
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
    private SerializerInterface $serializer;
    private UcService $ucService;

    /**
     * UserSubscriber constructor.
     * @param LayerService $layerService
     * @param UcService $ucService
     */
    public function __construct(LayerService $layerService, UcService $ucService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->serializer = $layerService->serializer;
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
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();
        $body = json_decode($event->getRequest()->getContent(), true);

        if (!$contentType) {
            $contentType = $event->getRequest()->headers->get('Accept');
        }

        switch ($contentType) {
            case 'application/json':
                $renderType = 'json';
                break;
            case 'application/ld+json':
                $renderType = 'jsonld';
                break;
            case 'application/hal+json':
                $renderType = 'jsonhal';
                break;
            default:
                $contentType = 'application/ld+json';
                $renderType = 'jsonld';
        }

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
        $response = $this->serializer->serialize(
            $response,
            $renderType,
        );
        $event->setResponse(new Response(
            $response,
            Response::HTTP_OK,
            ['content-type' => $contentType]
        ));
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
