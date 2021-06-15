<?php

namespace App\Tests\Service;

use App\Service\BsService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BsServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $bsService;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        $this->bsService = $this->serviceContainer->get(BsService::class);
        parent::setUp();
    }

    public function testSendInvitation()
    {
    }

    public function testSendPasswordChangedEmail()
    {
    }

    public function testSendPasswordResetMail()
    {
    }
}
