<?php

namespace App\Tests\Service;

use App\Service\EDUService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EDUServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $eduService;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        $this->eduService = $this->serviceContainer->get(EDUService::class);
        parent::setUp();
    }

    public function testUpdateParticipants()
    {
    }

    public function testDeleteParticipantGroups()
    {
    }

    public function testDeleteParticipants()
    {
    }

    public function testSaveEavParticipant()
    {
    }

    public function testChangeGroupTeachers()
    {
    }

    public function testDeleteResults()
    {
    }

    public function testGetEducationEvent()
    {
    }

    public function testGetGroup()
    {
    }

    public function testConvertEducationEvent()
    {
    }

    public function testGetEducationEvents()
    {
    }

    public function testDeleteGroup()
    {
    }

    public function testCreateEducationEvent()
    {
    }

    public function testSetGroupDetailsDates()
    {
    }

    public function testRemoveGroupFromParticipation()
    {
    }

    public function testDeleteEducationEvents()
    {
    }

    public function testSetGroupCourseDetails()
    {
    }

    public function testGetGroupsWithStatus()
    {
    }

    public function testConvertGroupObject()
    {
    }

    public function testCheckParticipationGroup()
    {
    }

    public function testDeleteEducationEvent()
    {
    }

    public function testUpdateEducationEvent()
    {
    }

    public function testGetParticipants()
    {
    }

    public function testGetProgram()
    {
    }

    public function testHasProgram()
    {
    }

    public function testSaveProgram()
    {
    }

    public function testGetGroups()
    {
    }

    public function testSaveEavResult()
    {
    }
}
