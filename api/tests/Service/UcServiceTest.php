<?php

namespace App\Tests\Service;

use App\Service\UcService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UcServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $ucService;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        $this->ucService = $this->serviceContainer->get(UcService::class);
        parent::setUp();
    }

    public function testLogin()
    {
        $jwt = $this->ucService->login('main+testadmin@conduction.nl', 'Test1234');

        $this->assertIsString($jwt);
    }

    public function testUpdateUserContactForEmployee()
    {
    }

    public function testValidateUserGroups()
    {
    }

    public function testCreateTaalhuisUserGroups()
    {
    }

    public function testGetUserRolesByOrganization()
    {
    }

    public function testWriteFile()
    {
    }

    public function testDeleteUser()
    {
    }

    public function testCreateUserObject()
    {
    }

    public function testCreateUser()
    {
    }

    public function testUpdatePasswordWithToken()
    {
    }

    public function testCreateTaalhuisEmployeeGroup()
    {
    }

    public function testCreateProviderVolunteerUserGroup()
    {
    }

    public function testCreateUserRoleObject()
    {
    }

    public function testUpdateUser()
    {
    }

    public function testDeleteUserGroups()
    {
    }

    public function testCreateProviderMentorUserGroup()
    {
    }

    public function testUserEnvironmentEnum()
    {
    }

    public function testValidateJWTAndGetPayload()
    {
    }

    public function testCreateProviderUserGroups()
    {
    }

    public function testCreatePasswordResetToken()
    {
    }

    public function testGetUser()
    {
    }

    public function testCreateProviderCoordinatorUserGroup()
    {
    }

    public function testCreateTaalhuisCoordinatorGroup()
    {
    }

    public function testGetUsers()
    {
    }

    public function testGetUserArray()
    {
    }

    public function testGetUserRoles()
    {
    }

    public function testLogout()
    {
    }

    public function testRemoveFiles()
    {
    }

    public function testCreateUserGroups()
    {
    }

    public function testCreateJWTToken()
    {
    }
}
