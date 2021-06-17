<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Registration;
use App\Entity\Student;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\RegistrationService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;
use Ramsey\Uuid\Uuid;

class RegistrationMutationResolver implements MutationResolverInterface
{
    private CommonGroundService $commonGroundService;
    private RegistrationService $registrationService;
    private CCService $ccService;
    private StudentService $studentService;
    private EDUService $eduService;
    private MrcService $mrcService;

    /**
     * RegistrationMutationResolver constructor.
     *
     * @param MrcService   $mrcService
     * @param LayerService $layerService
     */
    public function __construct(
        MrcService $mrcService,
        LayerService $layerService
    ) {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->registrationService = new RegistrationService($layerService->entityManager, $layerService->commonGroundService);
        $this->ccService = new CCService($layerService->entityManager, $layerService->commonGroundService);
        $this->studentService = new StudentService($layerService->entityManager, $layerService->commonGroundService);
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
        $this->mrcService = $mrcService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if ((!$item instanceof Registration && !key_exists('input', $context['info']->variableValues) || !$item instanceof Student && !key_exists('input', $context['info']->variableValues))) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createRegistration':
                return $this->createRegistration($context['info']->variableValues['input']);
            case 'removeRegistration':
                return $this->deleteRegistration($context['info']->variableValues['input']);
            case 'acceptRegistration':
                return $this->acceptRegistration($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    /**
     * Creates a registration.
     *
     * @param array $input the input data for the registration
     *
     * @throws Exception
     *
     * @return Registration The resulting registration object
     */
    public function createRegistration(array $input): Registration
    {
        $this->registrationService->checkRegistrationValues($input);

        //Save student person
        $registrationStudent = $this->inputToStudentPerson($input);
        $registrationStudent = $this->ccService->saveEavPerson($registrationStudent);

        //Save registrar person
        $registrationRegistrar = $this->inputToRegistrarPerson($input);
        $registrationRegistrar = $this->commonGroundService->saveResource($registrationRegistrar, ['component' => 'cc', 'type' => 'people']);

        //Save registrar organization
        $organization = $this->inputToOrganization($input, $registrationRegistrar['id']);
        $organization = $this->commonGroundService->saveResource($organization, ['component' => 'cc', 'type' => 'organizations']);

        //Save memo
        if (isset($input['memo'])) {
            $memo = $this->inputToMemo($input, $registrationStudent['@id']);
            $memo['author'] = $organization['@id'];
            $memo = $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'type' => 'memos']);
        }

        //Save participant
        $participant = $this->createParticipant($organization, $registrationStudent);

        if (!isset($input['languageHouseId'])) {
            throw new Exception('No Language House Id provided');
        }

        //update program
        $this->updateProgram($input, $participant);

        $resourceResult = $this->registrationService->handleResult(['student' => $registrationStudent, 'registration' => $registrationRegistrar, 'languageHouseId' => $input['languageHouseId'], 'participant' => $participant, 'memo' => $memo]);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    /**
     * Updates a program.
     *
     * @param array $input       the input data for the registration
     * @param array $participant the input data for the registration
     */
    public function updateProgram(array $input, array $participant)
    {
        $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $input['languageHouseId']]);
        $program = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'programs'], ['provider' => $languageHouseUrl])['hydra:member'][0];
        foreach ($program['participants'] as &$programParticipant) {
            $programParticipant = '/participants/'.$programParticipant['id'];
        }
        $program['participants'][] = '/participants/'.$participant['id'];
        $this->commonGroundService->saveResource($program, ['component' => 'edu', 'type' => 'programs', 'id' => $program['id']]);
    }

    /**
     * Creates a participant.
     *
     * @param array $organization        the organization data.
     * @param array $registrationStudent the registrationStudent data.
     *
     * @throws Exception
     *
     * @return array The resulting eav/participants object
     */
    public function createParticipant(array $organization, array $registrationStudent): array
    {
        $participant['referredBy'] = $organization['@id'];
        $participant['person'] = $registrationStudent['@id'];
        $participant['status'] = 'pending';

        return $this->eduService->saveEavParticipant($participant);
    }

    /**
     * Deletes a registration.
     *
     * @param array $input the input data.
     *
     * @throws Exception
     *
     * @return ?Registration The resulting Registration object
     */
    public function deleteRegistration(array $input): ?Registration
    {
        $result['result'] = [];

        $studentId = explode('/', $input['id']);
        if (is_array($studentId)) {
            $studentId = end($studentId);
        }
        $student = $this->studentService->getStudent($studentId);

        $result = array_merge($result, $this->registrationService->deleteRegistration($student));

        $result['result'] = false;
        if (isset($result['registration'])) {
            $result['result'] = true;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return null;
    }

    /**
     * Accept a registration.
     *
     * @param array $input the input data.
     *
     * @throws Exception
     *
     * @return object The resulting Registration object
     */
    public function acceptRegistration(array $input): object
    {
        $studentId = explode('/', $input['id']);
        if (is_array($studentId)) {
            $studentId = end($studentId);
        }
        $student = $this->studentService->getStudent($studentId);

        // Create mrc/employee and user
        $employee = ['person' => $student['person']['@id']];
        if (isset($student['person']['emails'][0]['email'])) {
            $employee['email'] = $student['person']['emails'][0]['email'];
        }
        $this->mrcService->createEmployeeArray($employee);

        $participant['status'] = 'accepted';
        $participant = $this->eduService->saveEavParticipant($participant, $student['participant']['@id']);
        $student['participant'] = $participant;
        $resourceResult = $this->studentService->handleResult($student, true);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    /**
     * Input to memo.
     *
     * @param array       $input      the input data.
     * @param string|null $studentUrl The studentUrl
     *
     * @throws Exception
     *
     * @return array The resulting memo properties
     */
    private function inputToMemo(array $input, string $studentUrl = null): array
    {
        $memo = $this->getMemoStudentProperties($studentUrl);
        $memo['description'] = $input['memo'];

        return $memo;
    }

    /**
     * Get memo Student properties.
     *
     * @param string $studentUrl the studentUrl.
     *
     * @throws Exception
     *
     * @return array The resulting registration properties
     */
    private function getMemoStudentProperties(string $studentUrl): array
    {
        $student = $this->commonGroundService->getResource($studentUrl);
        $memo['name'] = 'Memo about '.$student['givenName'];
        $memo['topic'] = $studentUrl;

        return $memo;
    }

    /**
     * Input to studentPerson.
     *
     * @param array $input the input data.
     *
     * @throws Exception
     *
     * @return array The resulting student properties
     */
    private function inputToStudentPerson(array $input)
    {
        $student = [];
        //Get student person inputs
        if (isset($input['student'])) {
            $student = $this->getStudentProperties($student, $input['student']);
        }

        return $student;
    }

    /**
     * Input to RegistrarPerson.
     *
     * @param array $input the input data.
     *
     * @throws Exception
     *
     * @return array The resulting registrar properties
     */
    private function inputToRegistrarPerson(array $input)
    {
        $registrar = [];
        //Get registrar person inputs
        if (isset($input['registrar'])) {
            $registrar = $this->getRegistrarProperties($registrar, $input['registrar']);
        }

        return $registrar;
    }

    /**
     * Get Student properties.
     *
     * @param array $registration the registration data.
     * @param array $studentInput the student input.
     *
     * @throws Exception
     *
     * @return array The resulting registration properties
     */
    private function getStudentProperties(array $registration, array $studentInput): array
    {
        if (isset($studentInput['givenName'])) {
            $registration['givenName'] = $studentInput['givenName'];
        }

        $registration['additionalName'] = $studentInput['additionalName'];

        if (isset($studentInput['familyName'])) {
            $registration['familyName'] = $studentInput['familyName'];
        }
        if (isset($studentInput['email'])) {
            $registration['emails'][0]['name'] = 'Email of '.$registration['givenName'];
            $registration['emails'][0]['email'] = $studentInput['email'];
        }
        if (isset($studentInput['telephone'])) {
            $registration['telephones'][0]['name'] = 'Telephone of '.$registration['givenName'];
            $registration['telephones'][0]['telephone'] = $studentInput['telephone'];
        }
        if (isset($studentInput['address'])) {
            $registration['addresses'][0]['name'] = 'Address of '.$registration['givenName'];
            $registration['addresses'][0] = $studentInput['address'];
        }

        return $registration;
    }

    /**
     * Get Registrar properties.
     *
     * @param array $registration   the registration data.
     * @param array $registrarInput the registrar input.
     *
     * @throws Exception
     *
     * @return array The resulting registration properties
     */
    private function getRegistrarProperties(array $registration, array $registrarInput): array
    {
        if (isset($registrarInput['organizationName'])) {
            $registration['organizationName'] = $registrarInput['organizationName'];
        }
        if (isset($registrarInput['givenName'])) {
            $registration['givenName'] = $registrarInput['givenName'];
        }
        if (isset($registrarInput['additionalName'])) {
            $registration['additionalName'] = $registrarInput['additionalName'];
        }
        if (isset($registrarInput['familyName'])) {
            $registration['familyName'] = $registrarInput['familyName'];
        }
        if (isset($registrarInput['email'])) {
            $registration['emails'][0]['name'] = 'Email of '.$registration['givenName'];
            $registration['emails'][0]['email'] = $registrarInput['email'];
        }
        if (isset($registrarInput['telephone'])) {
            $registration['telephones'][0]['name'] = 'Telephone of '.$registration['givenName'];
            $registration['telephones'][0]['telephone'] = $registrarInput['telephone'];
        }

        return $registration;
    }

    /**
     * Input to organization.
     *
     * @param array       $input      the input data.
     * @param string|null $ccPersonId the cc/people id.
     *
     * @throws Exception
     *
     * @return array The resulting registration properties
     */
    private function inputToOrganization(array $input, string $ccPersonId = null)
    {
        // Add cc/people to this cc/organization
        if (isset($ccPersonId)) {
            $organization['persons'][] = '/people/'.$ccPersonId;
        } else {
            $organization = [];
        }

        $organization['name'] = $input['registrar']['organizationName'];

        return $organization;
    }
}
