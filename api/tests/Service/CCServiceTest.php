<?php

namespace App\Tests\Service;

use App\Service\CCService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CCServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $ccService;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        $this->ccService = $this->serviceContainer->get(CCService::class);
        parent::setUp();
    }

    public function testCleanResource()
    {
    }

    public function testGetOrganization()
    {
    }

    public function testEmployeeToPerson()
    {
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
    }

    public function testConvertAddress()
    {
    }

    public function testUpdateOrganization()
    {
    }

    public function testDeleteOrganization()
    {
    }

    public function testCreateOrganizationObject()
    {
    }

    public function testCreatePersonForEmployee()
    {
    }

    public function testCreatePerson()
    {
    }

    public function testSaveEavPerson()
    {
    }

    public function testUpdatePerson()
    {
    }

    public function testGetOrganizations()
    {
    }
}
