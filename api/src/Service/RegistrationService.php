<?php

namespace App\Service;


use App\Entity\LanguageHouse;
use App\Entity\Provider;
use App\Entity\Registration;
use App\Entity\Student;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RegistrationService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;
    private StudentService $studentService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        ParameterBagInterface $parameterBag,
        CCService $ccService,
        StudentService $studentService
    ){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
        $this->ccService = $ccService;
        $this->studentService = $studentService;
    }

    public function getRegistration($languageHouseId)
    {
        $result['registration'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        return $result;
    }

    public function getRegistrations()
    {
        $result['registrations'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['type' => 'Taalhuis'])["hydra:member"];
        return $result;
    }

    public function deleteRegistration($id)
    {
        $participant = $this->commonGroundService->getResourceList(['component'=>'edu', 'type' => 'participants', 'id' => $id]);
        $studentPerson = $this->commonGroundService->getResource($participant['person']);
        $registrarOrganization = $this->commonGroundService->getResource($participant['referredBy']);
        $memo = $this->commonGroundService->getResourceList(['component'=>'memo', 'type' => 'memos'], ['topic' => $studentPerson['@id'], 'author' => $registrarOrganization['@id']])["hydra:member"][0];
        $registrarPerson = $this->commonGroundService->getResource($registrarOrganization['persons'][0]['@id']);

        $this->commonGroundService->deleteResource($memo);
        $this->commonGroundService->deleteResource($registrarPerson);
        $this->commonGroundService->deleteResource($studentPerson);
        $this->commonGroundService->deleteResource($registrarOrganization);
        $this->commonGroundService->deleteResource($participant);

        $result['registration'] = $participant;
        return $result;
    }

    public function acceptRegistration($id): Student
    {


//        $participant = $this->commonGroundService->updateResource($participant, ['component' => 'edu', 'type' => 'participants', 'id' => $id]);

//        return $resourceResult;

    }

    private function inputToPerson(array $student) {
        $person = [];

        $person = $this->getPersonPropertiesFromPersonDetails($person, $student);
        $person = $this->getPersonPropertiesFromReferrerDetails($person, $student);
        $person = $this->getPersonPropertiesFromContactDetails($person, $student);

        return $person;
    }

    private function getPersonPropertiesFromPersonDetails(array $person, array $student): array
    {
        $person['givenName'] = $student['givenName'];
        $person['additionalName'] = $student['additionalName'];
        $person['familyName'] = $student['familyName'];
        $person['gender'] = null;
        $person['birthday'] = null;

        return $person;
    }

    private function getPersonPropertiesFromContactDetails(array $person, array $student): array
    {
        $person['addresses'][0] = $student['addresses'];
        $person['emails'][0]['email'] = $student['emails'][0]['email'];
        $person['telephones'][0]['telephone'] = $student['telephones'][0]['telephone'];

        return $person;
    }

    private function getPersonPropertiesFromReferrerDetails(array $person, array $student): array
    {
        $person['referringOrganization'] = $student['givenName'];
        $person['referringOrganizationOther'] = null;
        $person['email'] = $student['familyName'];

        return $person;
    }

    public function handleResult($registrationStudent, $registrationRegistrar, $languageHouse, $participant, $memo)
    {
        $resource = new Registration();
        //@todo: setLanguageHouseId has to be set to the taalhuis where the student is referred to
        //@todo: remove address setRegistrar
        $resource->setLanguageHouseId($languageHouse);
        $resource->setStudent($registrationStudent);
        $resource->setRegistrar($registrationRegistrar);
        $resource->setMemo($memo['description']);
        $resource->setStudentId($participant['id']);
        $this->entityManager->persist($resource);
        return $resource;
    }

    public function checkRegistrationValues($input)
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
