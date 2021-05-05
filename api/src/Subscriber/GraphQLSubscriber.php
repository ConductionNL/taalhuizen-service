<?php


namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Taalhuis;
use App\Service\CCService;
use App\Service\UcService;
use App\Service\WRCService;
use App\Service\EDUService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

class GraphQLSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $em;
    private ParameterBagInterface $params;
    private UcService $ucService;

    public function __construct(UcService $ucService, EntityManagerInterface $em, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->params = $params;
        $this->ucService = $ucService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['login', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    //@TODO Errors in correct format
    public function login(RequestEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');

        $content = json_decode($event->getRequest()->getContent(), true);
        $graphQL = Parser::parse($content['query']);
        if(
            $graphQL->definitions->offsetGet(0)->name->value != 'loginUser' &&
            $graphQL->definitions->offsetGet(0)->name->value != 'requestPasswordResetUser' &&
            $graphQL->definitions->offsetGet(0)->name->value != 'resetPasswordUser'
        ) {
            $auth = $event->getRequest()->headers->get('Authorization');
            if(strpos($auth, 'Bearer') !== false){
                $token = str_replace('Bearer ', '', $auth);
                $payload = $this->ucService->validateJWTAndGetPayload($token);

                if(!$this->validatePayload($payload)){
                    throw new Exception('Token not valid');
                }
            }
            else {
                throw new Exception('No access token provided');
            }
        }
    }

    public function validatePayload(array $payload): bool
    {
        $now = new \DateTime();
        $checks['issuer']   = isset($payload['iss']) && $payload['iss'] == $this->params->get('app_url');
        $checks['type']     = isset($payload['type']) && $payload['type'] == 'login';
        $checks['expiry']   = isset($payload['exp']) && $payload['exp'] > $now->getTimestamp();
        $checks['issuance'] = isset($payload['ias']) && $payload['ias'] < $now->getTimestamp();

        foreach ($checks as $check){
            if(!$check){
                return false;
            }
        }
        return true;
    }
}
