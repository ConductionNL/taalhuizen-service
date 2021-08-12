<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Registration;
use App\Service\LayerService;
use App\Service\NewRegistrationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RegistrationItemSubscriber implements EventSubscriberInterface
{
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private NewRegistrationService $registrationService;

    /**
     * UserItemSubscriber constructor.
     *
     * @param LayerService           $layerService
     * @param NewRegistrationService $registrationService
     */
    public function __construct(LayerService $layerService, NewRegistrationService $registrationService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->registrationService = $registrationService;
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
     *
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
            case 'api_registrations_get_item':
                $response = $this->getRegistration($event->getRequest()->attributes->get('id'));
                break;
            case 'api_registrations_delete_item':
                $response = $this->registrationService->deleteRegistration($event->getRequest()->attributes->get('id'));
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
     * @throws Exception
     *
     * @return Registration|Response
     */
    private function getRegistration(string $id)
    {
        $userExists = $this->checkIfUserExists($id);
        if ($userExists instanceof Response) {
            return $userExists;
        }

        return $this->registrationService->getRegistration($id);
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    private function deleteUser(string $id): Response
    {
        $userExists = $this->checkIfUserExists($id);
        if ($userExists instanceof Response) {
            return $userExists;
        }

        try {
            $this->registrationService->deleteUser($id);

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
     * @param string $id
     *
     * @return Response|null
     */
    private function checkIfUserExists(string $id): ?Response
    {
        $userUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id]);
        if (!$this->commonGroundService->isResource($userUrl)) {
            return new Response(
                json_encode([
                    'message' => 'This registration does not exist!',
                    'path'    => '',
                    'data'    => ['user' => $userUrl],
                ]),
                Response::HTTP_NOT_FOUND,
                ['content-type' => 'application/json']
            );
        }

        return null;
    }
}
