<?php

namespace App\Tests\Service;

use App\Service\WRCService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WRCServiceTest extends KernelTestCase
{

    private $serviceContainer;
    private $WRCService;

    protected function setUp(): void
    {

        self::bootKernel();
        $this->serviceContainer = self::$container;
        $this->WRCService = $this->serviceContainer->get(WRCService::class);
        parent::setUp();
    }

    public function testGetOrganization()
    {

    }

    public function testRemoveDocument()
    {

    }

    public function testGetDocument()
    {

    }

    public function testHandleDocumentProps()
    {

    }

    public function testCreateOrganization()
    {

    }

    public function testGetDocuments()
    {

    }

    public function testCreateDocumentObject()
    {

    }

    public function testCreateDocument()
    {

    }

    public function testDownloadDocument()
    {

    }

    public function testSaveOrganization()
    {

    }

    public function testSetContact()
    {

    }

    public function testGetDocumentId()
    {

    }
}
