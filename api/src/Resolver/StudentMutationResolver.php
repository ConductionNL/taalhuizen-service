<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Service\StudentService;
use App\Service\EAVService;
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
    private EAVService $EAVService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, StudentService $studentService, EAVService $EAVService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
        $this->EAVService = $EAVService;
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
        switch ($context['info']->operation->name->value) {
            case 'createStudent':
                return $this->createStudent($context['info']->variableValues['input']);
            case 'updateStudent':
                return $this->updateStudent($context['info']->variableValues['input']);
            case 'removeStudent':
                return $this->removeStudent($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createStudent(array $input): Student
    {
        $result['result'] = [];

//         If languageHouseId is set generate the url for it
        $languageHouseUrl = null;
        if (isset($input['languageHouseId'])) {
            $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $input['languageHouseId']]);
        } else {
            throw new \Exception('languageHouseId not given');
        }

//        var_dump('test1');

        // First make cc/person
        $ccPersonUrl = $this->savePerson($input, $input['languageHouseId']);

        // Then make edu/participant
        $input['studentId'] = $this->saveParticipant($input, $ccPersonUrl, $languageHouseUrl);

        // Then make mrc/employee / mrc objects
        $this->saveMRCObjects($input, $ccPersonUrl);

        // Then make memo('s)
        $this->saveMemos($input, $ccPersonUrl);


        // Do some checks and error handling
        $result = array_merge($result, $this->studentService->checkStudentValues($input, $languageHouseUrl));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save Student and connect student/participant to it
//            $result = array_merge($result, $this->studentService->saveStudent($result['student'], $studentUrl));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->studentService->handleResult($result['student'], $input['studentId']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['student']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        return $resourceResult;
    }

//    public function updateStudent(array $input): Student
//    {
//        $result['result'] = [];
//
//        // If studentUrl or studentId is set generate the id for it, needed for eav calls later
//        $studentId = null;
//        if (isset($input['studentUrl'])) {
//            $studentId = $this->commonGroundService->getUuidFromUrl($input['studentUrl']);
//        } else {
//            $studentId = explode('/', $input['id']);
//            if (is_array($studentId)) {
//                $studentId = end($studentId);
//            }
//        }
//
//        // Transform input info to student body...
////        $student = $this->inputToStudent($input);
//
//        // Do some checks and error handling
//        $result = array_merge($result, $this->studentService->checkStudentValues($student, null, $studentId));
//
//        if (!isset($result['errorMessage'])) {
//            // No errors so lets continue... to:
//            // Save Student and connect student/participant to it
//            $result = array_merge($result, $this->studentService->saveStudent($result['student'], null, $studentId));
//
//            // Now put together the expected result in $result['result'] for Lifely:
//            $resourceResult = $this->studentService->handleResult($result['student'], $input['studentId']);
//            $resourceResult->setId(Uuid::getFactory()->fromString($result['student']['id']));
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
//        $this->entityManager->persist($resourceResult);
//        return $resourceResult;
//    }
//
//    public function removeStudent(array $student): ?Student
//    {
//        $result['result'] = [];
//
//        // If studentUrl or studentId is set generate the id for it, needed for eav calls later
//        $studentId = null;
//        if (isset($student['studentUrl'])) {
//            $studentId = $this->commonGroundService->getUuidFromUrl($student['studentUrl']);
//        } elseif (isset($student['id'])) {
//            $studentId = explode('/', $student['id']);
//            if (is_array($studentId)) {
//                $studentId = end($studentId);
//            }
//        } else {
//            throw new Exception('No studentUrl or id was specified');
//        }
//
//        $result = array_merge($result, $this->studentService->deleteStudent($studentId));
//
//        $result['result'] = False;
//        if (isset($result['student'])) {
//            $result['result'] = True;
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
//        return null;
//    }

    private function savePerson(array $input, string $languageHouseId): string
    {
        if (isset($input['studentId'])) {
            $person = $this->EAVService->getObject('person', null, 'cc', $input['studentId']);
        } else {
            $person = [];
        }
        $person['organization'] = '/organizations/' . $languageHouseId;
        if (isset($input['personDetails'])) {
            $person = $this->getPersonPropertiesFromPersonDetails($person, $input['personDetails']);
        }
        if (isset($input['contactDetails'])) {
            $person = $this->getPersonPropertiesFromContactDetails($person, $input['contactDetails']);
        }
        if (isset($input['generalDetails'])) {
            $person = $this->getPersonPropertiesFromGeneralDetails($person, $input['generalDetails']);
        }
        if (isset($input['backgroundDetails'])) {
            $person = $this->getPersonPropertiesFromBackgroundDetails($person, $input['backgroundDetails']);
        }
        if (isset($input['dutchNTDetails'])) {
            $person = $this->getPersonPropertiesFromDutchNTDetails($person, $input['dutchNTDetails']);
        }
        if (isset($input['availabilityDetails'])) {
            $person = $this->getPersonPropertiesFromAvailabilityDetails($person, $input['availabilityDetails']);
        }
        if (isset($input['permissionDetails'])) {
            $person = $this->getPersonPropertiesFromPermissionDetails($person, $input);
        }
        $person = $this->studentService->saveStudentResource($person, 'people');

        return $person['@id'];
    }

    private function saveMRCObjects(array $input, string $ccPersonId)
    {
//        MRC
//        if (isset($input['jobDetails'])) {
//            $person = $this->getPersonPropertiesFromJobDetails($person, $input);
//        }
//        EDU
//        if (isset($input['motivationDetails'])) {
//            $person = $this->getPersonPropertiesFromMotivationDetails($person, $input);
//        }
//        $person = $this->studentService->saveStudentResource($person, 'persons');

    }

    private function saveParticipant(array $input, string $ccPersonUrl, string $languageHouseUrl)
    {
        if (isset($ccPersonUrl)) {
            $participant = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], ['person' => $ccPersonUrl])['hydra:member'][0];
            $participant = $this->EAVService->getObject('participant', null, 'edu', $participant['id']);
        } else {
            $participant = [];
        }

        if (isset($input['referrerDetails'])) {
            $participant = $this->getParticipantPropertiesFromReferrerDetails($participant, $input['referrerDetails']);
        }

        // EAV or Result objects?
        if (isset($input['speakingLevel'])) {
            $participant['speakingLevel'] = $input['speakingLevel'];
        }
        if (isset($input['readingTestResult'])) {
            $participant['readingTestResult'] = $input['readingTestResult'];
        }
        if (isset($input['writingTestResult'])) {
            $participant['writingTestResult'] = $input['writingTestResult'];
        }

//        if (isset($input['educationDetails'])) {
//            $participant = $this->getParticipantPropertiesFromEducationDetails($participant, $input['educationDetails']);
//        }

//        if (isset($input['courseDetails'])) {
//            $participant = $this->getParticipantPropertiesFromCourseDetails($participant, $input['courseDetails']);
//        }

        $participant = $this->studentService->saveStudentResource($participant, 'participants');

        return $participant['id'];
    }

    private function saveMemos(array $input, string $ccPersonId)
    {
        if (isset($input['id'])) {
            $memos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['topic' => $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $input['id']])])['hydra:member'];
        } else {
            $memos = [];
        }
        if (isset($input['memo'])) {
            $memo = [];
            $memo['name'] = 'Student memo';
            $memo['description'] = $input['memo'];
            $memo['topic'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $input['id']]);
            $memos[] = $memo;

        }

        if (isset($input['availabilityDetails'])) {
            $memos[] = $this->getMemoFromAvailabilityDetails($ccPersonId, $input['availabilityDetails']);
        }

        foreach ($memos as $memo) {
            $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'types' => 'memos']);
        }

    }

    private function getMemoFromAvailabilityDetails(string $ccPersonId, array $availabilityDetails)
    {
        $memo['name'] = 'Availability notes';
        $memo['description'] = $availabilityDetails['availabilityNotes'];
        $memo['topic'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $ccPersonId]);

        return $memo;
    }

    private function getPersonPropertiesFromAvailabilityDetails(array $person, array $availabilityDetails)
    {
        if (isset($availabilityDetails['availability'])) {
            $person['availability'] = $availabilityDetails['availability'];
        }

        return $person;
    }


    private function getPersonPropertiesFromContactDetails(array $person, array $contactDetails): array
    {
        if (isset($contactDetails['contactPreference'])) {
            $person['contactPreference'] = $contactDetails['contactPreference'];
        } elseif ($contactDetails['contactPreferenceOther']) {
            $person['contactPreference'] = $contactDetails['contactPreferenceOther'];
        }

        return $person;
    }

    private function getPersonPropertiesFromGeneralDetails(array $person, array $generalDetails): array
    {
        if (isset($generalDetails['countryOfOrigin'])) {
            $person['birthplace'] = $generalDetails['countryOfOrigin'];
        }
        if (isset($generalDetails['nativeLanguage'])) {
            $person['primaryLanguage'] = $generalDetails['nativeLanguage'];
        }
        if (isset($generalDetails['otherLanguages'])) {
            $person['speakingLanguages'] = $generalDetails['otherLanguages'];
        }
        if (isset($generalDetails['familyComposition'])) {
            $person['maritalStatus'] = $generalDetails['familyComposition'];
        }
        if (isset($generalDetails['childrenDatesOfBirth'])) {
            foreach ($generalDetails['childrenDatesOfBirth'] as $child) {
                $person['dependents'][] = $child;
            }
        }

        return $person;
    }

    private function getPersonPropertiesFromPersonDetails(array $person, array $personDetails): array
    {
        if (isset($personDetails['givenName'])) {
            $person['givenName'] = $personDetails['givenName'];
        }
        if (isset($personDetails['additionalName'])) {
            $person['additionalName'] = $personDetails['additionalName'];
        }
        if (isset($personDetails['familyName'])) {
            $person['familyName'] = $personDetails['familyName'];
        }
        if (isset($personDetails['gender'])) {
            $person['gender'] = $personDetails['gender'];
        }
        if (isset($personDetails['dateOfBirth'])) {
            $person['birthday'] = $personDetails['dateOfBirth'];
        }

        return $person;
    }

    private function getParticipantPropertiesFromReferrerDetails(array $participant, array $referrerDetails): array
    {
        if (isset($participant['referredBy'])) {
            $referringOrganization = $this->commonGroundService->getResource($participant['referredBy']);
        } else {
            $referringOrganization = [];
        }
        if (isset($referrerDetails['referringOrganization'])) {
            $referringOrganization['name'] = $referrerDetails['referringOrganization'];
        } elseif (isset($referrerDetails['referringOrganizationOther'])) {
            $referringOrganization['name'] = $referrerDetails['referringOrganizationOther'];
        }
        if (isset($referringOrganization['id'])) {
            $referringOrganization = $this->commonGroundService->saveResource($referringOrganization, ['component' => 'cc', 'type' => 'organizations', 'id' => $referringOrganization['id']]);
        } else {
            $referringOrganization = $this->commonGroundService->saveResource($referringOrganization, ['component' => 'cc', 'type' => 'organizations']);
        }
        if (isset($referrerDetails['email'])) {
            if (isset($referringOrganization['emails'][0])) {
                $referringOrganization['emails'][0]['email'] = $referrerDetails['email'];
                $email = $this->commonGroundService->saveResource($referringOrganization['emails'][0], $referringOrganization['emails'][0]['@id']);
            } else {
                $email['name'] = 'Email ' . $referringOrganization['name'];
                $email['email'] = $referrerDetails['email'];
                $email['organization'] = '/organization/' . $referringOrganization['id'];
                $email = $this->commonGroundService->saveResource($email, ['component' => 'cc', 'type' => 'emails']);
            }
            $referringOrganization['emails'][0] = '/emails/' . $email['id'];
        }

        $participant['referredBy'] = $referringOrganization['@id'];

        return $participant;
    }

    private function getPersonPropertiesFromBackgroundDetails(array $person, array $backgroundDetails): array
    {
        if (isset($backgroundDetails['foundVia'])) {
            $person['foundVia'] = $backgroundDetails['foundVia'];
        } elseif (isset($backgroundDetails['foundViaOther'])) {
            $person['foundVia'] = $backgroundDetails['foundViaOther'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBefore'])) {
            $person['wentToTaalhuisBefore'] = (bool) $backgroundDetails['wentToTaalhuisBefore'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBeforeReason'])) {
            $person['wentToTaalhuisBeforeReason'] = $backgroundDetails['wentToTaalhuisBeforeReason'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBeforeYear'])) {
            $person['wentToTaalhuisBeforeYear'] = $backgroundDetails['wentToTaalhuisBeforeYear'];
        }
        if (isset($backgroundDetails['network'])) {
            $person['network'] = $backgroundDetails['network'];
        }
        if (isset($backgroundDetails['participationLadder'])) {
            $person['participationLadder'] = (int) $backgroundDetails['participationLadder'];
        }

        return $person;
    }

    private function getPersonPropertiesFromDutchNTDetails(array $person, array $dutchNTDetails): array
    {
        if (isset($dutchNTDetails['dutchNTLevel'])) {
            $person['dutchNTLevel'] = $dutchNTDetails['dutchNTLevel'];
        }
        if (isset($dutchNTDetails['inNetherlandsSinceYear'])) {
            $person['inNetherlandsSinceYear'] = $dutchNTDetails['inNetherlandsSinceYear'];
        }
        if (isset($dutchNTDetails['languageInDailyLife'])) {
            $person['languageInDailyLife'] = $dutchNTDetails['languageInDailyLife'];
        }
        if (isset($dutchNTDetails['knowsLatinAlphabet'])) {
            $person['knowsLatinAlphabet'] = (bool) $dutchNTDetails['knowsLatinAlphabet'];
        }
        if (isset($dutchNTDetails['lastKnownLevel'])) {
            $person['lastKnownLevel'] = $dutchNTDetails['lastKnownLevel'];
        }

        return $person;
    }

    private function getPersonPropertiesFromPermissionDetails(array $person, array $permissionDetails): array
    {
        if (isset($permissionDetails['didSignPermissionForm'])) {
            $person['didSignPermissionForm'] = $permissionDetails['didSignPermissionForm'];
        }
        if (isset($permissionDetails['hasPermissionToShareDataWithAanbieders'])) {
            $person['hasPermissionToShareDataWithAanbieders'] = $permissionDetails['hasPermissionToShareDataWithAanbieders'];
        }
        if (isset($permissionDetails['hasPermissionToShareDataWithLibraries'])) {
            $person['hasPermissionToShareDataWithLibraries'] = $permissionDetails['hasPermissionToShareDataWithLibraries'];
        }
        if (isset($permissionDetails['hasPermissionToSendInformationAboutLibraries'])) {
            $person['hasPermissionToSendInformationAboutLibraries'] = $permissionDetails['hasPermissionToSendInformationAboutLibraries'];
        }

        return $person;
    }
}
