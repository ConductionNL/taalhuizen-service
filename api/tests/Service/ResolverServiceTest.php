<?php

namespace App\Tests\Service;

use App\Service\ResolverService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResolverServiceTest extends KernelTestCase
{

    private $serviceContainer;
    private $resolverService;

    protected function setUp(): void
    {

        self::bootKernel();
        $this->serviceContainer = self::$container;
        $this->resolverService = $this->serviceContainer->get(ResolverService::class);
        parent::setUp();
    }


    public function testCreatePaginator()
    {

    }
}
