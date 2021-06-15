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
        $array = $this->ucService->validateJWTAndGetPayload('eyJhbGciOiJSUzUxMiJ9.eyJ1c2VySWQiOiI2ZWZjYzNkYy0wN2IzLTQ5YzQtOGU1ZS1jOGIwMTQyYTk4ODYiLCJ0eXBlIjoibG9naW4iLCJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3QiLCJpYXMiOjE2MjM3Nzg3ODIsImV4cCI6MTYyNDY0Mjc4Mn0.JMPcbFtp2ygTAwMbz1wtVeDo5LAY6Pr8bW5AMK8gKtw_DFYqpJEOf_qtqv6UHTJjCQt4J6iqTY7qLhUs9c7d6E-xapOEMewpyXj8APpV6WPXj4J06uxrgTr0PB45xvL637IoetkKnyg7ArHcfAGS2a8RU0R0MDLg5aPRgN7VxQeFz6jQVPRWtRU4rbOAaZthu6BoYaBS79LM6fZx4tgygr3roIU88uEjpZyLBrrR3zXi_IMT0uEZtiQwl0B39BmHl6grCCcDPOE_gNibjUvsVQ-HU7IzY10uiQixShZ0Ko-EKEfuD_D4dVuxZI1NmJ8uh1y5pG29BwLiANVisDkiJsqiF_kd9vr9Klbb3282Ew0wRiaz8oLGApyPp-d3g6BdBgdJD1QaDZrZOJDyxiLHOlHPJoTNTRCA9zKJpaXW-cADSnoYkpScehmdCWZEaZTb4wGw6G4Qr0kbxw6FtWwrPH-ykZgha-359Jpk--4GOUjmMybXs57K2u9X8SJ3ev6DvzDUsyKUsZeQLh6qKYTCFFsw5DdwbFBUBqpHOFT1Nhslxfqe9iNAJUnAVpJ5CFuU9Ii0_EmwDC9GF92B3EZbZhHAcEtYWO2zb0YSiO0kdaqm6QQnfZyJ5z018KM66CdVh9V4YaaTohS5OLAU7wdTtRaYR8Q5ZeR_luDQxgfwuao');
        $this->assertIsArray($array);
    }

    public function testCreateProviderUserGroups()
    {
    }

    public function testCreatePasswordResetToken()
    {
        $token = $this->ucService->createPasswordResetToken('main+testadmin@conduction.nl', true);

        $this->assertIsString($token);
        $this->assertNotEquals('', $token);
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
