<?php

namespace App\Service;

use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ErrorSerializerService
{
    private SerializerService $serializerService;

    public function __construct(SerializerService $serializerService)
    {
        $this->serializerService = $serializerService;
    }

    public function serialize(BadRequestPathException $exception, RequestEvent $event): void
    {
        $exceptionArray = new ArrayCollection([
            'message'   => $exception->getMessage(),
            'path'      => $exception->getPath(),
            'data'      => $exception->getData(),
        ]);

        $content = $this->serializerService->serialize($exceptionArray, 'json', []);
        $event->setResponse(new Response($content, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']));
    }
}
