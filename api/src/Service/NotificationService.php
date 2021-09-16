<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NotificationService
{
    private CommonGroundService $commonGroundService;
    private ParameterBagInterface $parameterBag;

    /**
     * BsService constructor.
     *
     * @param CommonGroundService $commonGroundService
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(CommonGroundService $commonGroundService, ParameterBagInterface $parameterBag)
    {
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param string $service
     * @param string $type
     */
    public function processNotification(string $service, string $type)
    {
        //TODO switch for service
        //TODO switch for type
    }
}
