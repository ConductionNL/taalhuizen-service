<?php


namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Example;
use App\Service\ExampleService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

// TODO:delete this subscriber
class ExampleSubscriber implements EventSubscriberInterface
{
    private $em;
    private $params;
    private $commonGroundService;
    private $exampleService;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, CommongroundService $commonGroundService, exampleService $exampleService)
    {
        $this->em = $em;
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->exampleService = $exampleService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['example', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function example(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        if($route != 'api_examples_get_collection' && $route != 'api_examples_post_collection'){
            return;
        }

        if($route == 'api_examples_post_collection'){
            $result = $this->exampleService->saveExample($resource);
        }
        else{
            $result = $this->exampleService->getExample($resource);
        }

        $response = new Response(
            json_encode($result),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );

        $event->setResponse($response);
    }
}
