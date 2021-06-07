<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Taalhuis;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\WRCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TaalhuisSubscriber implements EventSubscriberInterface
{
    private $em;
    private $params;
    private $commonGroundService;
    private $ccService;
    private $wrcService;
    private $eduService;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, CommongroundService $commonGroundService, CCService $ccService, WRCService $wrcService, EDUService $eduService)
    {
        $this->em = $em;
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->ccService = $ccService;
        $this->wrcService = $wrcService;
        $this->eduService = $eduService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['taalhuis', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function taalhuis(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        if ($route != 'api_taalhuis_get_collection'
            && $route != 'api_taalhuis_get_taalhuis_collection'
            && $route != 'api_taalhuis_delete_taalhuis_collection'
            && $route != 'api_taalhuis_post_collection') {
            return;
        }

        // this: is only here to make sure result has a result and that this is always shown first in the response body
        $result['result'] = [];

        //Handle post
        if ($route == 'api_taalhuis_post_collection' and $resource instanceof Taalhuis) {
            $taalhuis = $this->dtoToTaalhuis($resource);

            //create cc organization
            $ccOrganization = $this->ccService->saveOrganization($taalhuis, 'taalhuis');
            //create wrc organization
            $wrcOrganization = $this->wrcService->saveOrganization($taalhuis);
            //connect orgs
            $taalhuis = $this->ccService->saveOrganization($ccOrganization, null, $wrcOrganization['@id']);
            $wrcOrganization = $this->wrcService->saveOrganization($wrcOrganization, $taalhuis['@id']);

            //make program so courses can be added later
            if (!$this->eduService->hasProgram($wrcOrganization)) {
                $this->eduService->saveProgram($wrcOrganization);
            }
            // Add $taalhuis to the $result['taalhuis'] because this is convenient when testing or debugging (mostly for us)
            $result['taalhuis'] = $taalhuis;
            // Now put together the expected result in $result['result'] for Lifely:
            $result['result'] = $this->handleResult($taalhuis);

            // If any error was caught set $result['result'] to null
            if (isset($result['errorMessage'])) {
                $result['result'] = null;
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

    private function dtoToTaalhuis($resource)
    {
        if ($resource->getId()) {
            $taalhuis['id'] = $resource->getId();
        }
        $taalhuis['name'] = $resource->getName();
        $taalhuis['address'] = $resource->getAddress();
        $taalhuis['email'] = $resource->getEmail();
        $taalhuis['phoneNumber'] = $resource->getPhoneNumber();

        return $taalhuis;
    }

    private function handleResult($taalhuis)
    {
        return [
            'id'        => $taalhuis['id'],
            'name'      => $taalhuis['name'],
            'address'   => $taalhuis['address'],
            'email'     => $taalhuis['email'],
            'telephone' => $taalhuis['telephone'],
            'type'      => $taalhuis['type'],
        ];
    }
}
