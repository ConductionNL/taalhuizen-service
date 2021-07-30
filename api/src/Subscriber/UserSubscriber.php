<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\LayerService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
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
        $this->commonGroundService = $layerService->commonGroundService;
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
                return; // do not use break here
//            case 'api_users_logout_collection': //TODO:
//                $response = $this->logout($resource);
//                $this->serializerService->setResponse($response, $event, ['token']);
//                return; // do not use break here
            case 'api_users_request_password_reset_collection':
                $response = $this->requestPasswordReset($resource);
                $this->serializerService->setResponse($response, $event, ['token']);
                return; // do not use break here
            case 'api_users_reset_password_collection':
                $response = $this->resetPassword($resource);
                break;
            case 'api_users_post_collection':
                $response = $this->createUser($resource);
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
     * handle login.
     *
     * @param User $resource
     *
     * @return User
     */
    private function login(User $resource): User
    {
        $user = new User();
        $user->setToken($this->ucService->login($resource->getUsername(), $resource->getPassword()));
        $this->entityManager->persist($user);

        return $user;
    }

    // TODO:
    /**
     * handle logout.
     *
     * @param User $resource
     *
     * @return User
     */
    private function logout(User $resource): User
    {
        //TODO:
        $user = new User();
//        $this->ucService->logout();
        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * handle password reset request.
     *
     * @param User $resource
     *
     * @return User
     */
    private function requestPasswordReset(User $resource): User
    {
        $user = new User();
        $user->setToken($this->ucService->createPasswordResetToken($resource->getUsername()));
        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * handle password reset.
     *
     * @param User $resource
     *
     * @return User|Response
     * @throws Exception
     */
    private function resetPassword(User $resource)
    {
        return $this->ucService->updatePasswordWithToken($resource->getUsername(), $resource->getToken(), $resource->getPassword());
    }

    /**
     * @param User $user
     * @return User|Response
     */
    private function createUser(User $user)
    {
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => str_replace('+','%2B',$user->getUsername())])['hydra:member'];
        if (count($users) > 0) {
            return new Response(
                json_encode([
                    'message' => 'A user with this username already exists!',
                    'path'    => 'username',
                    'data'    => ['username' => $user->getUsername()],
                ]),
                Response::HTTP_CONFLICT,
                ['content-type' => 'application/json']
            );
        }

        return $this->ucService->createUser($user);
    }
}
