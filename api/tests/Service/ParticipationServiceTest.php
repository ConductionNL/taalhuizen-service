<?php

namespace App\Tests\Service;

use App\Service\ParticipationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParticipationServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $participationService;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        $this->participationService = $this->serviceContainer->get(ParticipationService::class);
        parent::setUp();
    }

    public function testCheckLevel()
    {
    }

    public function testUpdateParticipation()
    {
    }

    public function testCheckGroupInput()
    {
    }

    public function testGetParticipation()
    {
    }

    public function testErrorRemoveMentorFromParticipation()
    {
    }

    public function testCheckParticipationValuesPresenceDates()
    {
    }

    public function testAddMentoredParticipationToEmployee()
    {
    }

    public function testCheckAanbieder()
    {
    }

    public function testHandleParticipationStatus()
    {
    }

    public function testCheckMentor()
    {
    }

    public function testRemoveGroupFromParticipation()
    {
    }

    public function testRemoveMentorFromParticipation()
    {
    }

    public function testCheckMentorGroup()
    {
    }

    public function testCheckParticipationValuesDates()
    {
    }

    public function testErrorRemoveGroupFromParticipation()
    {
    }

    public function testCheckTopic()
    {
    }

    public function testCheckMentorInput()
    {
    }

    public function testCheckEAVGroup()
    {
    }

    public function testCheckApplication()
    {
    }

    public function testCheckGroup()
    {
    }

    public function testCheckParticipationId()
    {
    }

    public function testAddMentorToParticipation()
    {
    }

    public function testHandleGettingParticipation()
    {
    }

    public function testGetEmployeeParticipations()
    {
    }

    public function testHandleResult()
    {
    }

    public function testCheckParticipationRequiredFields()
    {
    }

    public function testCheckAanbiederUrl()
    {
    }

    public function testAddGroupToParticipation()
    {
    }

    public function testGetParticipations()
    {
    }

    public function testHandleParticipationDates()
    {
    }

    public function testCheckLearningNeedId()
    {
    }

    public function testHandleResultJson()
    {
    }

    public function testDeleteParticipation()
    {
    }

    public function testUpdateParticipationStatus()
    {
    }

    public function testSaveParticipation()
    {
    }

    public function testGetParticipationId()
    {
    }

    public function testGetParticipationUrl()
    {
    }

    public function testCheckParticipationValues()
    {
    }
}
