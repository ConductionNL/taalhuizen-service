<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Registration;
use App\Entity\Student;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\RegistrationService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;

class RegistrationMutationResolver implements MutationResolverInterface
{
    private CommonGroundService $commonGroundService;
    private RegistrationService $registrationService;
    private CCService $ccService;
    private StudentService $studentService;
    private EDUService $eduService;

    public function __construct(
        CommonGroundService $commonGroundService,
        RegistrationService $registrationService,
        CCService $ccService,
        StudentService $studentService,
        EDUService $eduService
    ) {
        $this->commonGroundService = $commonGroundService;
        $this->registrationService = $registrationService;
        $this->ccService = $ccService;
        $this->studentService = $studentService;
        $this->eduService = $eduService;
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
        $memo = $this->inputToMemo($input, $registrationStudent['@id'], $organization['@id']);
        $memo = $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'type' => 'memos']);

        //Save participant
        $participant = $this->createParticipant($organization, $registrationStudent);

        if (!isset($input['languageHouseId'])) {
            throw new Exception('No Language House Id provided');
        }

        //update program
        $this->updateProgram($input, $participant);

        $resourceResult = $this->registrationService->handleResult($participant, $registrationRegistrar, $input['languageHouseId'], $participant, $memo);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    public function updateProgram($input, $participant)
    {
        $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type'=>'organizations', 'id' => $input['languageHouseId']]);
        $program = $this->commonGroundService->getResourceList(['component' => 'edu', 'type'=>'programs'], ['provider' => $languageHouseUrl])['hydra:member'][0];
        foreach ($program['participants'] as &$programParticipant) {
            $programParticipant = '/participants/'.$programParticipant['id'];
        }
        $program['participants'][] = '/participants/'.$participant['id'];
        $this->commonGroundService->saveResource($program, ['component' => 'edu', 'type'=>'programs', 'id' => $program['id']]);
    }

    public function createParticipant($organization, $registrationStudent)
    {
        $participant['referredBy'] = $organization['@id'];
        $participant['person'] = $registrationStudent['@id'];
        $participant['status'] = 'pending';
        return $this->eduService->saveEavParticipant($participant);
    }

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

    public function acceptRegistration(array $input): object
    {
        $studentId = explode('/', $input['id']);
        if (is_array($studentId)) {
            $studentId = end($studentId);
        }
        $student = $this->studentService->getStudent($studentId);

        $participant['status'] = 'accepted';
        $participant = $this->eduService->saveEavParticipant($participant, $student['participant']['@id']);
        $student['participant'] = $participant;
        $resourceResult = $this->studentService->handleResult($student, true);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    private function inputToMemo(array $input, $studentUrl = null, $organizationUrl = null)
    {
        $memo = [];
        if (isset($input['memo'])) {
            $memo = $this->getMemoProperties($memo, $input['memo'], $studentUrl, $organizationUrl);
        }

        return $memo;
    }

    private function inputToStudentPerson(array $input)
    {
        $student = [];
        //Get student person inputs
        if (isset($input['student'])) {
            $student = $this->getStudentProperties($student, $input['student']);
        }

        return $student;
    }

    private function inputToRegistrarPerson(array $input)
    {
        $registrar = [];
        //Get registrar person inputs
        if (isset($input['registrar'])) {
            $registrar = $this->getRegistrarProperties($registrar, $input['registrar']);
        }

        return $registrar;
    }

    private function getMemoProperties(array $registration, string $memoInput, $studentUrl, $organizationUrl): array
    {
        $registration['author'] = $organizationUrl;
        $student = $this->commonGroundService->getResource($studentUrl);
        $registration['name'] = 'Memo about '.$student['givenName'];
        $registration['topic'] = $studentUrl;
        if (isset($memoInput)) {
            $registration['description'] = $memoInput;
        }

        return $registration;
    }

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
