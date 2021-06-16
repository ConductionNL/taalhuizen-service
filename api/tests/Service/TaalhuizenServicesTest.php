<?php

namespace App\Tests\Service;

use App\Service\TestResultService;
use App\Service\UcService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaalhuizenServicesTest extends KernelTestCase
{
    private $serviceContainer;

    //services
    private $bsService;
    private $ccService;
    private $eavService;
    private $eduService;
    private $learningNeedService;
    private $mrcService;
    private $participationService;
    private $registrationSservice;
    private $resolverService;
    private $studentService;
    private $testResultService;
    private $ucService;
    private $wrcService;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        $this->ucService = $this->serviceContainer->get(UcService::class);
        parent::setUp();
    }

    public function testLogin()
    {
        $jwt = $this->ucService->login('main+testadmin@conduction.nl', 'Test1234');

        $this->assertIsString($jwt);
    }

    public function testCreateOrganization()
    {
        $providerArray = [
            'name'        => 'test provider',
            'email'       => 'test@email.com',
            'phoneNumber' => '0612345678',
            'address'     => [
                'street'            => 'test',
                'houseNumber'       => '412',
                'houseNumberSuffix' => 'b',
                'postalCode'        => '1234AB',
                'locality'          => 'Almere',
            ],
        ];

        $array = $this->ccService->createOrganization($providerArray, 'Aanbieder');

        $this->assertIsArray($array);

        return $array;
    }

    public function testGetOrganizations()
    {
        $taalHuisOrganizations = $this->ccService->getOrganizations('Taalhuis');
        $organizations = $this->ccService->getOrganizations('none');

        $this->assertIsObject($taalHuisOrganizations);
        $this->assertIsObject($organizations);
    }

}
