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
        self::bootKernel();
        $this->serviceContainer = self::$container;
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
