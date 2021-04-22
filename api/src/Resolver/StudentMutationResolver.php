<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use App\Entity\Address;
use App\Entity\Document;
use App\Entity\Employee;
use App\Entity\LanguageHouse;
use App\Entity\Student;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class StudentMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, StudentService $studentService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Student && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createStudent':
                return $this->createStudent($item);
            case 'updateStudent':
                return $this->updateStudent($context['info']->variableValues['input']);
            case 'removeStudent':
                return $this->removeStudent($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createStudent(Student $resource): Student
    {
        $result['result'] = [];

        // Not needed for student?
        // If studentId is set generate the url for it
//        $studentUrl = null;
//        if ($resource->getStudentId()) {
//            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $resource->getStudentId()]);
//        }

        // Transform DTO info to student body...
        $student = $this->dtoToStudent($resource);

        // Not needed for student?
        // Do some checks and error handling
//        $result = array_merge($result, $this->studentService->checkStudentValues($student, $studentUrl));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save Student and connect student/participant to it
//            $result = array_merge($result, $this->studentService->saveStudent($result['student'], $studentUrl));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->studentService->handleResult($result['student'], $resource->getStudentId());
            $resourceResult->setId(Uuid::getFactory()->fromString($result['student']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        return $resourceResult;
    }

    public function updateStudent(array $input): Student
    {
        $result['result'] = [];

        // If studentUrl or studentId is set generate the id for it, needed for eav calls later
        $studentId = null;
        if (isset($input['studentUrl'])) {
            $studentId = $this->commonGroundService->getUuidFromUrl($input['studentUrl']);
        } else {
            $studentId = explode('/', $input['id']);
            if (is_array($studentId)) {
                $studentId = end($studentId);
            }
        }

        // Transform input info to student body...
        $student = $this->inputToStudent($input);

        // Do some checks and error handling
        $result = array_merge($result, $this->studentService->checkStudentValues($student, null, $studentId));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save Student and connect student/participant to it
            $result = array_merge($result, $this->studentService->saveStudent($result['student'], null, $studentId));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->studentService->handleResult($result['student'], $input['studentId']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['student']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function removeStudent(array $student): ?Student
    {
        $result['result'] = [];

        // If studentUrl or studentId is set generate the id for it, needed for eav calls later
        $studentId = null;
        if (isset($student['studentUrl'])) {
            $studentId = $this->commonGroundService->getUuidFromUrl($student['studentUrl']);
        } elseif (isset($student['id'])) {
            $studentId = explode('/', $student['id']);
            if (is_array($studentId)) {
                $studentId = end($studentId);
            }
        } else {
            throw new Exception('No studentUrl or id was specified');
        }

        $result = array_merge($result, $this->studentService->deleteStudent($studentId));

        $result['result'] = False;
        if (isset($result['student'])) {
            $result['result'] = True;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        return null;
    }

    private function dtoToStudent(Student $resource)
    {
        // Get all info from the dto for creating a Student and return the body for this
        if ($resource->getCivicIntegrationDetails()) {
            $student['civicIntegrationDetails'] = $resource->getCivicIntegrationDetails();
        }
        if ($resource->getPersonDetails()) {
            $student['personDetails'] = $resource->getPersonDetails();
        }
        if ($resource->getContactDetails()) {
            $student['contactDetails'] = $resource->getContactDetails();
        }
        if ($resource->getGeneralDetails()) {
            $student['generalDetails'] = $resource->getGeneralDetails();
        }
        if ($resource->getReferrerDetails()) {
            $student['referrerDetails'] = $resource->getReferrerDetails();
        }
        if ($resource->getBackgroundDetails()) {
            $student['backgroundDetails'] = $resource->getBackgroundDetails();
        }
        if ($resource->getDutchNTDetails()) {
            $student['dutchNTDetails'] = $resource->getDutchNTDetails();
        }
        if ($resource->getSpeakingLevel()) {
            $student['speakingLevel'] = $resource->getSpeakingLevel();
        }
        if ($resource->getEducationDetails()) {
            $student['educationDetails'] = $resource->getEducationDetails();
        }
        if ($resource->getCourseDetails()) {
            $student['courseDetails'] = $resource->getCourseDetails();
        }
        if ($resource->getJobDetails()) {
            $student['jobDetails'] = $resource->getJobDetails();
        }
        if ($resource->getMotivationDetails()) {
            $student['motivationDetails'] = $resource->getMotivationDetails();
        }
        if ($resource->getAvailabilityDetails()) {
            $student['availabilityDetails'] = $resource->getAvailabilityDetails();
        }
        if ($resource->getReadingTestResult()) {
            $student['readingTestResult'] = $resource->getReadingTestResult();
        }
        if ($resource->getWritingTestResult()) {
            $student['writingTestResult'] = $resource->getWritingTestResult();
        }
        if ($resource->getPermissionDetails()) {
            $student['permissionDetails'] = $resource->getPermissionDetails();
        }
        if ($resource->getIntakeDetail()) {
            $student['intakeDetail'] = $resource->getIntakeDetail();
        }
        if ($resource->getIntakeDetail()) {
            $student['intakeDetail'] = $resource->getIntakeDetail();
        }
        if ($resource->getTaalhuisId()) {
            $student['taalhuisId'] = $resource->getTaalhuisId();
        }
        if ($resource->getStudentId()) {
            $student['studentId'] = $resource->getStudentId();
        }
        return $student;
    }

    private function inputToStudent(array $input) {
        // Get all info from the input array for updating a Student and return the body for this
        if (isset($input['civicIntegrationDetails'])) {
            $student['civicIntegrationDetails'] = $input['civicIntegrationDetails'];
        }
        if (isset($input['personDetails'])) {
            $student['personDetails'] = $input['personDetails'];
        }
        if (isset($input['contactDetails'])) {
            $student['contactDetails'] = $input['contactDetails'];
        }
        if (isset($input['generalDetails'])) {
            $student['generalDetails'] = $input['generalDetails'];
        }
        if (isset($input['referrerDetails'])) {
            $student['referrerDetails'] = $input['referrerDetails'];
        }
        if (isset($input['backgroundDetails'])) {
            $student['backgroundDetails'] = $input['backgroundDetails'];
        }
        if (isset($input['dutchNTDetails'])) {
            $student['dutchNTDetails'] = $input['dutchNTDetails'];
        }
        if (isset($input['speakingLevel'])) {
            $student['speakingLevel'] = $input['speakingLevel'];
        }
        if (isset($input['educationDetails'])) {
            $student['educationDetails'] = $input['educationDetails'];
        }
        if (isset($input['courseDetails'])) {
            $student['courseDetails'] = $input['courseDetails'];
        }
        if (isset($input['jobDetails'])) {
            $student['jobDetails'] = $input['jobDetails'];
        }
        if (isset($input['motivationDetails'])) {
            $student['motivationDetails'] = $input['motivationDetails'];
        }
        if (isset($input['availabilityDetails'])) {
            $student['availabilityDetails'] = $input['availabilityDetails'];
        }
        if (isset($input['readingTestResult'])) {
            $student['readingTestResult'] = $input['readingTestResult'];
        }
        if (isset($input['writingTestResult'])) {
            $student['writingTestResult'] = $input['writingTestResult'];
        }
        if (isset($input['intakeDetail'])) {
            $student['intakeDetail'] = $input['intakeDetail'];
        }
        if (isset($input['taalhuisId'])) {
            $student['taalhuisId'] = $input['taalhuisId'];
        }
        if (isset($input['studentId'])) {
            $student['studentId'] = $input['studentId'];
        }
        return $student;
    }
}
