<?php

namespace App\Tests\Service;

use App\Service\LearningNeedService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LearningNeedServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $learningNeedService;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        $this->learningNeedService = $this->serviceContainer->get(LearningNeedService::class);
        parent::setUp();
    }

    public function testHandleParticipantLearningNeeds()
    {
    }

    public function testDeleteLearningNeed()
    {
    }

    public function testAddStudentToLearningNeed()
    {
    }

    public function testCheckLearningNeedValues()
    {
    }

    public function testGetStudentLearningNeed()
    {
    }

    public function testDeleteLearningNeedParticipations()
    {
    }

    public function testRemoveLearningNeedFromStudent()
    {
    }

    public function testGetLearningNeeds()
    {
    }

    public function testGetLearningNeed()
    {
    }

    public function testSetResourceParticipations()
    {
    }

    public function testSaveLearningNeed()
    {
    }

    public function testRemoveParticipantsFromLearningNeed()
    {
    }

    public function testHandleResult()
    {
    }
}
