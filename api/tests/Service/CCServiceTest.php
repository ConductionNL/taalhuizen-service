<?php

namespace App\Tests\Service;

use App\Entity\Provider;
use App\Service\CCService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CCServiceTest extends KernelTestCase
{
    private $serviceContainer;
    private $ccService;

    protected function setUp(): void
    {
        $this->serviceContainer = static::getContainer();
        $this->ccService = $this->serviceContainer->get(CCService::class);
        parent::setUp();
    }

    public function testEmployeeToPerson()
    {
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

        $array = $this->ccService->createOrganization($providerArray, 'Aanbieder');

        $this->assertIsArray($array);

        return $array;
    }

    /**
     * @depends testCreateOrganization
     */
    public function testCreateOrganizationObject($organization)
    {
        $object = $this->ccService->createOrganizationObject($organization, 'Aanbieder');

        $this->assertInstanceOf(Provider::class, $object);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testGetOrganization($organization)
    {
        $object = $this->ccService->getOrganization($organization['id'], 'Aanbieder');
        $this->assertInstanceOf(Provider::class, $object);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testCleanResource($array)
    {
        $result = $this->ccService->cleanResource($array);

        $this->assertIsArray($result);
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

        $result = $this->ccService->updateOrganization($organization['id'], $array);

        $this->assertIsArray($result);
    }

    /**
     * @depends testCreateOrganization
     */
    public function testConvertAddress($organization)
    {
        $result = $this->ccService->convertAddress($organization['addresses'][0]);

        $this->assertIsArray($result);
    }

    public function testGetOrganizations()
    {
        $taalHuisOrganizations = $this->ccService->getOrganizations('Taalhuis');
        $organizations = $this->ccService->getOrganizations('none');

        $this->assertIsObject($taalHuisOrganizations);
        $this->assertIsObject($organizations);

    }

    public function testDeleteOrganization()
    {
    }

    public function testCreatePersonForEmployee()
    {
    }

    public function testCreatePerson()
    {
    }

    public function testSaveEavPerson()
    {
    }

    public function testUpdatePerson()
    {
    }

}
