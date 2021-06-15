<?php

namespace App\Tests\Service;

use App\Service\TestResultService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestResultServiceTest extends KernelTestCase
{

    private $serviceContainer;
    private $testResultService;

    protected function setUp(): void
    {

        self::bootKernel();
        $this->serviceContainer = self::$container;
        $this->testResultService = $this->serviceContainer->get(TestResultService::class);
        parent::setUp();
    }

    public function testHandleResult()
    {

    }

    public function testCheckTestResultValues()
    {

    }

    public function testDeleteTestResult()
    {

    }

    public function testSaveTestResult()
    {

    }

    public function testGetTestResult()
    {

    }

    public function testGetTestResults()
    {

    }
}
