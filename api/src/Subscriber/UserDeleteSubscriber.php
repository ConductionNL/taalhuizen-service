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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserDeleteSubscriber implements EventSubscriberInterface
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
            KernelEvents::REQUEST => ['user', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function user(RequestEvent $event)
    {
        if(!$event->isMainRequest()){
            return;
        }
        $path = $event->getRequest()->getPathInfo();
        $route = $event->getRequest()->attributes->get('_route');
        $method = $event->getRequest()->getMethod();
        if(strpos($route, 'api_users_delete_item') !== false && $method == 'DELETE'){
            $response = $this->deleteUser($event->getRequest()->attributes->get('id'));
//            die;
            $event->setResponse($response);
        }
//        $resource = $event->getControllerResult();
        // Lets limit the subscriber
//        switch ($route) {
//            case 'api_users_delete_item':
//                $this->deleteUser($resource, $event);
//                break;
//            default:
//                return;
//        }
    }

    private function deleteUser(string $id): Response
    {
        $this->ucService->deleteUser($id);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
