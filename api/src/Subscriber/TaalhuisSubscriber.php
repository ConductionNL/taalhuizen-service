<?php


namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Taalhuis;
use App\Service\CCService;
use App\Service\WRCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

class TaalhuisSubscriber implements EventSubscriberInterface
{
    private $em;
    private $params;
    private $commonGroundService;
    private $ccService;
    private $wrcService;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, CommongroundService $commonGroundService, CCService $ccService, WRCService $wrcService)
    {
        $this->em = $em;
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->ccService = $ccService;
        $this->wrcService = $wrcService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['Taalhuis', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function taalhuis(ViewEvent $event){
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        if($route != 'api_taalhuis_get_collection' && $route != 'api_taalhuis_post_collection'){
            return;
        }

        //Handle post
        if ($route == 'api_taalhuis_post_collection' and $resource instanceof Taalhuis){
            // this: is only here to make sure result is always shown first in the response body
            $result['result'] = [];


            $taalhuis = $this->dtoToTaalhuis($resource);

            //create cc organization
            $ccOrganization = $this->ccService->saveOrganization($taalhuis, 'taalhuis');
            //create wrc organization
            $wrcOrganization = $this->wrcService->saveOrganization($taalhuis);
            //connect orgs
            $ccOrganization =  $this->ccService->saveOrganization($ccOrganization,null,$wrcOrganization['@id']);
            $wrcOrganization = $this->wrcService->saveOrganization($wrcOrganization, $ccOrganization['@id']);



        }
    }
    private function dtoToTaalhuis($resource)
    {
        if ($resource->getId()){
            $taalhuis['id'] = $resource->getId();
        }
        $taalhuis['name'] = $resource->getName();

        $taalhuis['address'] = $resource->getAddress();
        $taalhuis['email'] = $resource->getEmail();
        $taalhuis['phoneNumber'] = $resource->getPhoneNumber();
        return $taalhuis;
    }
    private function handleResult($taalhuis) {
        return [
            'id' => $taalhuis['id'],
            'name' => $taalhuis['name'],
            'address' => $taalhuis['address'],
            'email' => $taalhuis['email'],
            'telephone' => $taalhuis['telephone']
        ];
    }
}
