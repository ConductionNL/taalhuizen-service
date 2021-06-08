<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Provider;
use App\Service\CCService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AanbiederSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['provider', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function provider(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        if ($route != 'api_providers_get_collection'
            && $route != 'api_providers_get_provider_collection'
            && $route != 'api_providers_delete_provider_collection'
            && $route != 'api_providers_post_collection') {
            return;
        }

        // this: is only here to make sure result has a result and that this is always shown first in the response body
        $result['result'] = [];

        // Handle a post collection
        if ($route == 'api_providers_post_collection' and $resource instanceof Provider) {

                // No errors so lets continue... to: get all DTO info and save this in the correct places
//                $aanbieder = $this->dtoAanbieder($resource);
//
//                // Save the Aanbieder
//                $aanbieder = $this->ccService->createResource($aanbieder, 'providers');
//
//                // Add $aanbieder to the $result['aanbieder'] because this is convenient when testing or debugging (mostly for us)
//                $result['aanbieder'] = $aanbieder;
//                // Now put together the expected result in $result['result'] for Lifely:
//                $result['result'] = $this->handleResult($aanbieder);
//                $result= $this->ccService->saveOrganization($resource);
        }

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

    private function dtoAanbieder($resource)
    {
        // Get all info from the dto for creating/updating a Aanbieder and return the body for this
        $aanbieder['name'] = $resource->getName();
        $aanbieder['phonenumber'] = $resource->getPhoneNumber();
        $aanbieder['email'] = $resource->getEmail();
        $aanbieder['address'] = $resource->getAddress();
        $aanbieder['type'] = 'Aanbieder';

        return $aanbieder;
    }

    private function handleResult($aanbieder)
    {
        // Put together the expected result for Lifely:
        return [
            'id'        => $aanbieder['id'],
            'name'      => $aanbieder['name'],
            'address'   => $aanbieder['address'],
            'email'     => $aanbieder['email'],
            'telephone' => $aanbieder['phonenumber'],
            'type'      => $aanbieder['type'],
        ];
    }
}
