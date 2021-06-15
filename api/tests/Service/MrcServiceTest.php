<?php

namespace App\Tests\Service;

use App\Service\MrcService;
use App\Service\TestResultService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MrcServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $mrcService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->serviceContainer = self::$container;
        $this->mrcService = $this->serviceContainer->get(MrcService::class);
        parent::setUp();
    }

    public function testUpdateUser()
    {

    }

    public function testGetEmployeeByPersonUrl()
    {

    }

    public function testCreateCourse()
    {

    }

    public function testGetContact()
    {

    }

    public function testCreateUnfinishedEducation()
    {

    }

    public function testCreateEmployeeObject()
    {

    }

    public function testUpdateEmployee()
    {

    }

    public function testDeleteEmployee()
    {

    }

    public function testCreateEducations()
    {

    }

    public function testCreateEmployee()
    {

    }

    public function testCreateCompetences()
    {

    }

    public function testHandleUserGroups()
    {

    }

    public function testSetCurrentCourse()
    {

    }

    public function testSaveEmployeeEducations()
    {

    }

    public function testCheckIfUserExists()
    {

    }

    public function testHandleEducationType()
    {

    }

    public function testDeleteEmployees()
    {

    }

    public function testConvertUserRole()
    {

    }

    public function testSetCurrentEducation()
    {

    }

    public function testHandleUserOrganizationUrl()
    {

    }

    public function testHandleEducationStartDate()
    {

    }

    public function testCreateCurrentEducation()
    {

    }

    public function testHandleUserRoleArray()
    {

    }

    public function testGetEducation()
    {

    }

    public function testHandleEmployeeSkills()
    {

    }

    public function testCreateEmployeeResource()
    {

    }

    public function testSetContact()
    {

    }

    public function testGetEmployeeRaw()
    {

    }

    public function testSetUserRoleArray()
    {

    }

    public function testDeleteSubObjects()
    {

    }

    public function testCreateInterests()
    {

    }

    public function testGetUser()
    {

    }

    public function testSaveUser()
    {

    }

    public function testCreateUser()
    {

    }

    public function testGetEmployee()
    {

    }

    public function testHandleRetrievingContact()
    {

    }

    public function testGetEmployees()
    {

    }

    public function testHandleEducationEndDate()
    {

    }
}
