<?php

namespace App\Tests\Service;

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
            'givenName' => 'Test',
            'additionalName' => '',
            'familyName' => 'testing',
            'telephone' => '0612345678',
            'availabilities' => [
                'mon' => 'true',
                'tue' => 'false',
                'wed' => 'false',
                'thur' => 'true',
                'sat' => 'true',
                'sun' => 'false'
            ],
            'availabilityNotes' => 'test',
            'email' => "test+employee+{$organization['id']}@test.nl",
            'userGroupIds' => ["5ef493ac-9c96-45a7-a8be-07fa6cc4301d", "00848014-63f6-4350-b125-25c68b8631d4"],
            'gender' => 'X',
            'dateOfBirth' => '1-1-2000',
            'address' => [
                'street' => 'test',
                'houseNumber' => '5',
                'locality' => 'Almere',
                'postalCode' => '1234AB'
            ],
            'contactTelephone' => '0612345678',
            'contactPreference' => 'EMAIL',
            'contactPreferenceOther' => 'EMAIL',
            'targetGroupPreferences' => ['nt1'],
            'volunteeringPreference' => 'test',
            'gotHereVia' => 'conduction',
            'hasExperienceWithTargetGroup' => true,
            'experienceWithTargetGroupYesReason' => true,
            'currentEducation' => 'NO_BUT_DID_FOLLOW',
            'currentEducationYes' => [
                'dateSince' => '2020-05-10',
                'name' => 'something',
                'doesProvideCertificate' => true
            ],
            'currentEducationNoButDidFollow' => [
                'dateUntil' => '2020-05-10',
                'name' => 'something',
                'gotCertificate' => true,
                'level' => '5'
            ],
            'doesCurrentlyFollowCourse' => true,
            'currentlyFollowingCourseName' => 'course',
            'currentlyFollowingCourseInstitute' => 'institute',
            'currentlyFollowingCourseTeacherProfessionalism' => 'VOLUNTEER',
            'currentlyFollowingCourseCourseProfessionalism' => 'VOLUNTEER',
            'doesCurrentlyFollowingCourseProvideCertificate' => true,
            'otherRelevantCertificates' => 'MSC of Computer Science',
            'isVOGChecked' => true,
            'providerId' => $organization['id']
        ];

        $result = $this->serviceContainer->get(MrcService::class)->createEmployee($employeeArray);

        $this->assertIsObject($result);
    }

}
