<?php

namespace App\Service;

use App\Entity\Registration;
use App\Entity\Student;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class RegistrationService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = new EAVService($commonGroundService);
    }

    /**
     * This function gets a cc/organization with the given languageHouseId.
     *
     * @param string $languageHouseId Id of the cc/organizations
     *
     * @throws Exception
     *
     * @return array A cc/organizations is returned from the CC
     */
    public function getRegistration(string $languageHouseId): array
    {
        $result['registration'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);

        return $result;
    }

    /**
     * This function gets all cc/organizations with type->Taalhuis.
     *
     * @throws Exception
     *
     * @return array cc/organizations are returned from the CC
     */
    public function getRegistrations(): array
    {
        $result['registrations'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['type' => 'Taalhuis'])['hydra:member'];

        return $result;
    }

    /**
     * This function deletes a Registration with the given student.
     *
     * @param array $student Array with data from the edu/participants
     *
     * @throws Exception
     *
     * @return array A Registration is returned from the CC
     */
    public function deleteRegistration(array $student): array
    {
        $organization = $this->commonGroundService->getResource($student['participant']['referredBy']);
        $memo = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['topic' => $student['person']['@id'], 'author' => $organization['@id']])['hydra:member'][0];
        $registrarPerson = $this->commonGroundService->getResource($organization['persons'][0]['@id']);

        $this->deleteOrganization($organization['id']);
        $this->deleteMemo($memo['id']);
        $this->deleteRegistrarPerson($registrarPerson['id']);
        $this->deleteStudentPerson($student['person']['id']);
        $participation = $this->deleteParticipant($student['participant']['id']);

        $result['registration'] = $participation;

        return $result;
    }

    /**
     * This function deletes a cc/organization with the given id.
     *
     * @param string $id Id of the cc/organizations
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteOrganization(string $id): bool
    {
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'organizations', 'id' => $id]);

        return true;
    }

    /**
     * This function deletes a memo/memos with the given id.
     *
     * @param string $id Id of the memo/memos
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteMemo(string $id): bool
    {
        $this->commonGroundService->deleteResource(null, ['component'=>'memo', 'type' => 'memos', 'id' => $id]);

        return true;
    }

    /**
     * This function deletes the registrar person - cc/people with the given id.
     *
     * @param string $id Id of the cc/people
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteRegistrarPerson(string $id): bool
    {
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'people', 'id' => $id]);

        return true;
    }

    /**
     * This function deletes the student person - eav/cc/people with the given id.
     *
     * @param string $id Id of the cc/people
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteStudentPerson(string $id): bool
    {
        $this->eavService->deleteObject(null, 'people', $this->commonGroundService->cleanUrl(['component'=>'cc', 'type' => 'people', 'id' => $id]), 'cc');
        $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'people', 'id' => $id]);

        return true;
    }

    /**
     * This function deletes the participant - eav/edu/participants with the given id.
     *
     * @param string $id Id of the cc/people
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteParticipant(string $id): bool
    {
        $this->eavService->deleteObject(null, 'participants', $this->commonGroundService->cleanUrl(['component'=>'edu', 'type' => 'participants', 'id' => $id]), 'edu');
        $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'participants', 'id' => $id]);

        return true;
    }

    /**
     * This function convert the input to Person.
     *
     * @param array $student Array with data from the edu/participants
     *
     * @throws Exception
     *
     * @return array returns an array
     */
    private function inputToPerson(array $student): array
    {
        $person = [];

        $person = $this->getPersonPropertiesFromPersonDetails($person, $student);
        $person = $this->getPersonPropertiesFromReferrerDetails($person, $student);
        $person = $this->getPersonPropertiesFromContactDetails($person, $student);

        return $person;
    }

    /**
     * This function sets the given person and student to PersonDetails.
     *
     * @param array $person  Array with data from the cc/people
     * @param array $student Array with data from the edu/participants
     *
     * @throws Exception
     *
     * @return array returns an array
     */
    private function getPersonPropertiesFromPersonDetails(array $person, array $student): array
    {
        $person['givenName'] = $student['givenName'];
        $person['additionalName'] = $student['additionalName'];
        $person['familyName'] = $student['familyName'];
        $person['gender'] = null;
        $person['birthday'] = null;

        return $person;
    }

    /**
     * This function sets the given person and student to ContactDetails.
     *
     * @param array $person  Array with data from the cc/people
     * @param array $student Array with data from the edu/participants
     *
     * @throws Exception
     *
     * @return array returns an array
     */
    private function getPersonPropertiesFromContactDetails(array $person, array $student): array
    {
        $person['addresses'][0] = $student['addresses'];
        $person['emails'][0]['email'] = $student['emails'][0]['email'];
        $person['telephones'][0]['telephone'] = $student['telephones'][0]['telephone'];

        return $person;
    }

    /**
     * This function sets the given person and student to ReferrerDetails.
     *
     * @param array $person  Array with data from the cc/people
     * @param array $student Array with data from the edu/participants
     *
     * @throws Exception
     *
     * @return array returns an array
     */
    private function getPersonPropertiesFromReferrerDetails(array $person, array $student): array
    {
        $person['referringOrganization'] = $student['givenName'];
        $person['referringOrganizationOther'] = null;
        $person['email'] = $student['familyName'];

        return $person;
    }

    /**
     * This function handles the Registration result.
     *
     * @param array $registration Array with data from the registrationStudent, registrationRegistrar, cc/organization, edu/participant & the memo
     *
     * @throws Exception
     *
     * @return Registration returns a Registration object
     */
    public function handleResult(array $registration): Registration
    {
        $resource = new Registration();
        //@todo: setLanguageHouseId has to be set to the taalhuis where the student is referred to
        //@todo: remove address setRegistrar
        $resource->setLanguageHouseId($registration['languageHouseId']);
        $resource->setStudent($registration['registrationStudent']);
        $resource->setRegistrar($registration['registrationRegistrar']);
        $resource->setMemo($registration['memo']['description']);
        $resource->setStudentId($registration['registrationStudent']['id']);
        $this->entityManager->persist($resource);

        return $resource;
    }

    /**
     * This function checks the Registration values.
     *
     * @param array $input Array with data from the input
     *
     * @throws Exception
     */
    public function checkRegistrationValues(array $input)
    {
        // todo: make sure every subresource json array from the input follows the rules (required, enums, etc) from the corresponding entities!
        $student = $input['student'];
        if (empty($student['givenName']) || empty($student['familyName']) || empty($student['telephone']) || empty($student['email'])) {
            throw new Exception('Invalid request, Student: fill in the mandatory input fields');
        }
        $registrar = $input['registrar'];
        if (empty($registrar['givenName']) || empty($registrar['familyName']) || empty($registrar['telephone']) || empty($registrar['email'])) {
            throw new Exception('Invalid request, Registrar: fill in the mandatory input fields');
        }
    }
}
