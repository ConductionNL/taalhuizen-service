<?php

namespace App\Tests\Service;

use App\Service\EAVService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EAVServiceTest extends KernelTestCase
{

    private $serviceContainer;
    private $eavService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serviceContainer = self::$container;
        $this->eavService = $this->serviceContainer->get(EAVService::class);
        parent::setUp();
    }

    public function testDeleteObject()
    {

    }

    public function testSaveObject()
    {

    }

    public function testGetObject()
    {

    }

    public function testDeleteResource()
    {

    }

    public function testHasEavObject()
    {

    }

    public function testGetObjectList()
    {

    }
}
