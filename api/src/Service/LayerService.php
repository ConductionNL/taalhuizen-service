<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LayerService
{
    public CommonGroundService $commonGroundService;
    public EntityManagerInterface $entityManager;
    public ParameterBagInterface $parameterBag;
    public BsService $bsService;
    public TestResultService $testResultService;
    public RegistrationService $registrationService;

    /**
     * LayerService constructor.
     *
     * @param CommonGroundService    $commonGroundService
     * @param EntityManagerInterface $entityManager
     * @param ParameterBagInterface  $parameterBag
     * @param BsService              $bsService
     * @param TestResultService      $testResultService
     * @param RegistrationService    $registrationService
     */
    public function __construct(
        CommonGroundService $commonGroundService,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        BsService $bsService,
        TestResultService $testResultService,
        RegistrationService $registrationService
    ) {
        $this->commonGroundService = $commonGroundService;
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->bsService = $bsService;
        $this->testResultService = $testResultService;
        $this->registrationService = $registrationService;
    }
}
