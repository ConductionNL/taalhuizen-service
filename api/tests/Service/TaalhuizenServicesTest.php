<?php

namespace App\Tests\Service;

use App\Entity\Employee;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\MrcService;
use App\Service\UcService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaalhuizenServicesTest extends KernelTestCase
{
    private $serviceContainer;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        parent::setUp();
    }

    public function testLogin()
    {
        $jwt = $this->serviceContainer->get(UcService::class)->login('main+testadmin@conduction.nl', 'Test1234');

        $this->assertIsString($jwt);
    }

    public function testValidateJWTAndGetPayload()
    {
        $array = $this->serviceContainer->get(UcService::class)->validateJWTAndGetPayload('eyJhbGciOiJSUzUxMiJ9.eyJ1c2VySWQiOiI2ZWZjYzNkYy0wN2IzLTQ5YzQtOGU1ZS1jOGIwMTQyYTk4ODYiLCJ0eXBlIjoibG9naW4iLCJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3QiLCJpYXMiOjE2MjM3Nzg3ODIsImV4cCI6MTYyNDY0Mjc4Mn0.JMPcbFtp2ygTAwMbz1wtVeDo5LAY6Pr8bW5AMK8gKtw_DFYqpJEOf_qtqv6UHTJjCQt4J6iqTY7qLhUs9c7d6E-xapOEMewpyXj8APpV6WPXj4J06uxrgTr0PB45xvL637IoetkKnyg7ArHcfAGS2a8RU0R0MDLg5aPRgN7VxQeFz6jQVPRWtRU4rbOAaZthu6BoYaBS79LM6fZx4tgygr3roIU88uEjpZyLBrrR3zXi_IMT0uEZtiQwl0B39BmHl6grCCcDPOE_gNibjUvsVQ-HU7IzY10uiQixShZ0Ko-EKEfuD_D4dVuxZI1NmJ8uh1y5pG29BwLiANVisDkiJsqiF_kd9vr9Klbb3282Ew0wRiaz8oLGApyPp-d3g6BdBgdJD1QaDZrZOJDyxiLHOlHPJoTNTRCA9zKJpaXW-cADSnoYkpScehmdCWZEaZTb4wGw6G4Qr0kbxw6FtWwrPH-ykZgha-359Jpk--4GOUjmMybXs57K2u9X8SJ3ev6DvzDUsyKUsZeQLh6qKYTCFFsw5DdwbFBUBqpHOFT1Nhslxfqe9iNAJUnAVpJ5CFuU9Ii0_EmwDC9GF92B3EZbZhHAcEtYWO2zb0YSiO0kdaqm6QQnfZyJ5z018KM66CdVh9V4YaaTohS5OLAU7wdTtRaYR8Q5ZeR_luDQxgfwuao');
        $this->assertIsArray($array);
    }

    public function testCreateOrganization()
    {
        $providerArray = [
            'name'        => 'test provider',
            'email'       => 'test@email.com',
            'phoneNumber' => '0612345678',
            'address'     => [
                'street'            => 'test',
                'houseNumber'       => '412',
                'houseNumberSuffix' => 'b',
                'postalCode'        => '1234AB',
                'locality'          => 'Almere',
            ],
        ];

        $array = $this->serviceContainer->get(CCService::class)->createOrganization($providerArray, 'Aanbieder');

        $this->assertIsArray($array);

        return $array;
    }

    /**
     * @depends testCreateOrganization
     */
    public function testSaveProgram($organization)
    {
        $result = $this->serviceContainer->get(EDUService::class)->saveProgram($organization);

        $this->assertIsArray($result);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testCreateUserGroups($organization)
    {
        $group = $this->serviceContainer->get(UcService::class)->createUserGroups($organization, 'group');
        $taalhuisGroup = $this->serviceContainer->get(UcService::class)->createUserGroups($organization, 'Taalhuis');

        $this->assertIsArray($group);
        $this->assertIsArray($taalhuisGroup);
    }

    public function testGetOrganizations()
    {
        $taalHuisOrganizations = $this->serviceContainer->get(CCService::class)->getOrganizations('Taalhuis');
        $organizations = $this->serviceContainer->get(CCService::class)->getOrganizations('none');

        $this->assertIsObject($taalHuisOrganizations);
        $this->assertIsObject($organizations);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testUpdateOrganization($organization)
    {
        $array = [
            'name'        => 'test provider',
            'email'       => 'test@email.com',
            'phoneNumber' => '0612345678',
            'address'     => [
                'street'            => 'test',
                'houseNumber'       => '412',
                'houseNumberSuffix' => 'b',
                'postalCode'        => '1234AB',
                'locality'          => 'Almere',
            ],
        ];

        $result = $this->serviceContainer->get(CCService::class)->updateOrganization($organization['id'], $array);

        $this->assertIsArray($result);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testGetUserRolesByOrganization($organization)
    {
        $result = $this->serviceContainer->get(UcService::class)->getUserRolesByOrganization($organization['id'], 'provider');

        $this->assertIsObject($result);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testCreateEmployee($organization)
    {
        $employeeArray = [
            'givenName'      => 'Test',
            'additionalName' => '',
            'familyName'     => 'testing',
            'telephone'      => '0612345678',
            'availabilities' => [
                'mon'  => 'true',
                'tue'  => 'false',
                'wed'  => 'false',
                'thur' => 'true',
                'sat'  => 'true',
                'sun'  => 'false',
            ],
            'availabilityNotes' => 'test',
            'email'             => "test+employee+{$organization['id']}@test.nl",
            'userGroupIds'      => ['5ef493ac-9c96-45a7-a8be-07fa6cc4301d', '00848014-63f6-4350-b125-25c68b8631d4'],
            'gender'            => 'X',
            'dateOfBirth'       => '1-1-2000',
            'address'           => [
                'street'      => 'test',
                'houseNumber' => '5',
                'locality'    => 'Almere',
                'postalCode'  => '1234AB',
            ],
            'contactTelephone'                   => '0612345678',
            'contactPreference'                  => 'EMAIL',
            'contactPreferenceOther'             => 'EMAIL',
            'targetGroupPreferences'             => ['nt1'],
            'volunteeringPreference'             => 'test',
            'gotHereVia'                         => 'conduction',
            'hasExperienceWithTargetGroup'       => true,
            'experienceWithTargetGroupYesReason' => true,
            'currentEducation'                   => 'NO_BUT_DID_FOLLOW',
            'currentEducationYes'                => [
                'dateSince'              => '2020-05-10',
                'name'                   => 'something',
                'doesProvideCertificate' => true,
            ],
            'currentEducationNoButDidFollow' => [
                'dateUntil'      => '2020-05-10',
                'name'           => 'something',
                'gotCertificate' => true,
                'level'          => '5',
            ],
            'doesCurrentlyFollowCourse'                      => true,
            'currentlyFollowingCourseName'                   => 'course',
            'currentlyFollowingCourseInstitute'              => 'institute',
            'currentlyFollowingCourseTeacherProfessionalism' => 'VOLUNTEER',
            'currentlyFollowingCourseCourseProfessionalism'  => 'VOLUNTEER',
            'doesCurrentlyFollowingCourseProvideCertificate' => true,
            'otherRelevantCertificates'                      => 'MSC of Computer Science',
            'isVOGChecked'                                   => true,
            'providerId'                                     => $organization['id'],
        ];

        $result = $this->serviceContainer->get(MrcService::class)->createEmployee($employeeArray);

        $this->assertIsObject($result);
        $this->assertInstanceOf(Employee::class, $result);

        return $result;
    }

    /**
     * @depends testCreateOrganization
     * @depends testCreateEmployee
     */
    public function testUpdateEmployee($organization, $employee)
    {
        $employeeArray = [
            'userId'         => $employee->getUserId(),
            'givenName'      => 'Test',
            'additionalName' => '',
            'familyName'     => 'testing',
            'telephone'      => '0612345678',
            'availabilities' => [
                'mon'  => 'true',
                'tue'  => 'false',
                'wed'  => 'false',
                'thur' => 'true',
                'sat'  => 'true',
                'sun'  => 'false',
            ],
            'availabilityNotes' => 'test',
            'email'             => "test+employee+{$organization['id']}@test.nl",
            'userGroupIds'      => ['5ef493ac-9c96-45a7-a8be-07fa6cc4301d', '00848014-63f6-4350-b125-25c68b8631d4'],
            'gender'            => 'X',
            'dateOfBirth'       => '1-1-2000',
            'address'           => [
                'street'      => 'test',
                'houseNumber' => '5',
                'locality'    => 'Almere',
                'postalCode'  => '1234AB',
            ],
            'contactTelephone'                   => '0612345678',
            'contactPreference'                  => 'EMAIL',
            'contactPreferenceOther'             => 'EMAIL',
            'targetGroupPreferences'             => ['nt1'],
            'volunteeringPreference'             => 'test',
            'gotHereVia'                         => 'conduction',
            'hasExperienceWithTargetGroup'       => true,
            'experienceWithTargetGroupYesReason' => true,
            'currentEducation'                   => 'NO_BUT_DID_FOLLOW',
            'currentEducationYes'                => [
                'dateSince'              => '2020-05-10',
                'name'                   => 'something',
                'doesProvideCertificate' => true,
            ],
            'currentEducationNoButDidFollow' => [
                'dateUntil'      => '2020-05-10',
                'name'           => 'something',
                'gotCertificate' => true,
                'level'          => '5',
            ],
            'doesCurrentlyFollowCourse'                      => true,
            'currentlyFollowingCourseName'                   => 'course',
            'currentlyFollowingCourseInstitute'              => 'institute',
            'currentlyFollowingCourseTeacherProfessionalism' => 'VOLUNTEER',
            'currentlyFollowingCourseCourseProfessionalism'  => 'VOLUNTEER',
            'doesCurrentlyFollowingCourseProvideCertificate' => true,
            'otherRelevantCertificates'                      => 'MSC of Computer Science',
            'isVOGChecked'                                   => true,
            'providerId'                                     => $organization['id'],
        ];

        $id = explode('/', $employee->getId());

        $result = $this->serviceContainer->get(MrcService::class)->updateEmployeeArray(end($id), $employeeArray);

        $this->assertIsArray($result);
    }

    /**
     * @depends testCreateEmployee
     */
    public function testGetEmployee($employee)
    {
        $id = explode('/', $employee->getId());

        $result = $this->serviceContainer->get(MrcService::class)->getEmployee(end($id));

        $this->assertIsObject($result);
        $this->assertInstanceOf(Employee::class, $result);
    }
}
