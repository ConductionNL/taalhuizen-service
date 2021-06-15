<?php

namespace App\Tests\Service;

use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RegistrationServiceTest extends KernelTestCase
{

    private $serviceContainer;
    private $registrationService;

    protected function setUp(): void
    {

        self::bootKernel();
        $this->serviceContainer = self::$container;
        $this->registrationService = $this->serviceContainer->get(RegistrationService::class);
        parent::setUp();
    }

    public function testGetRegistration()
    {

    }

    public function testDeleteStudentPerson()
    {

    }

    public function testDeleteParticipant()
    {

    }

    public function testDeleteOrganization()
    {

    }

    public function testDeleteRegistration()
    {

    }

    public function testGetRegistrations()
    {

    }

    public function testDeleteRegistrarPerson()
    {

    }

    public function testDeleteMemo()
    {

    }

    public function testCheckRegistrationValues()
    {

    }

    public function testHandleResult()
    {

    }
}
