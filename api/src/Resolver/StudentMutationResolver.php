<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\MrcService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use App\Entity\Student;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\SerializerInterface;

class StudentMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;
    private CCService $ccService;
    private EDUService $eduService;
    private MrcService $mrcService;
    private SerializerInterface $serializer;

    public function __construct
    (
        EntityManagerInterface $entityManager,
        CommongroundService $commonGroundService,
        StudentService $studentService,
        CCService $ccService,
        EDUService $eduService,
        MrcService $mrcService,
        SerializerInterface $serializer
    )
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
        $this->ccService = $ccService;
        $this->eduService = $eduService;
        $this->mrcService = $mrcService;
        $this->serializer = $serializer;
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

    /**
     * @throws Exception
     */
    public function createStudent(array $input): Student
    {
        if (isset($input['languageHouseId'])) {
            $languageHouseId = explode('/', $input['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
            $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        } else {
            throw new \Exception('languageHouseId not given');
        }

        // Do some checks and error handling
        $this->studentService->checkStudentValues($input, $languageHouseUrl);

        //todo: only get dto info here in resolver, saving objects should be moved to the studentService->saveStudent, for an example see TestResultService->saveTestResult

        // Transform DTO info to cc/person body
        $person = $this->inputToPerson($input, $languageHouseId);
        // Save cc/person
        $person = $this->ccService->saveEavPerson($person);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input, $person['@id'], $languageHouseUrl);
        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant);

        $employee = $this->inputToEmployee($input, $person['@id']);
        // Save mrc/employee
        $employee = $this->mrcService->createEmployee($employee, true);
        if (isset($employee['educations'])) {
            foreach ($employee['educations'] as &$education) {
                $education = $this->eavService->getObject('education', $education['@id'], 'mrc');
            }
        }

//        // Then save memo('s)
//        $this->saveMemos($input, $ccPersonUrl);

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->studentService->handleResult($person, $participant, $employee);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    public function updateStudent(array $input): Student
    {
        $studentId = explode('/', $input['id']);
        if (is_array($studentId)) {
            $studentId = end($studentId);
        }
        $student = $this->studentService->getStudent($studentId);

        // Do some checks and error handling
        $this->studentService->checkStudentValues($input);

        //todo: only get dto info here in resolver, saving objects should be moved to the studentService->saveStudent, for an example see TestResultService->saveTestResult

        // Transform DTO info to cc/person body
        $person = $this->inputToPerson($input);
        // Save cc/person
        $person = $this->ccService->saveEavPerson($person, $student['person']['@id']);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input);
        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant, $student['participant']['@id']);

        // todo: w.i.p...
//        // Then save mrc/employee / mrc objects
//        $this->saveMRCObjects($input, $ccPersonUrl);
//
//        // Then save memo('s)
//        $this->saveMemos($input, $ccPersonUrl);

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->studentService->handleResult($person, $participant, null);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    //todo:
    public function removeStudent(array $student): ?Student
    {
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
        return null;
    }

    //todo: should be done in StudentService, for examples see StudentService->saveStudent or TestResultService->saveTestResult
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

    //todo: should be done in StudentService
    private function getMemoFromAvailabilityDetails(string $ccPersonId, array $availabilityDetails)
    {
        $memo['name'] = 'Availability notes';
        $memo['description'] = $availabilityDetails['availabilityNotes'];
        $memo['topic'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $ccPersonId]);

        return $memo;
    }

    private function inputToPerson(array $input, string $languageHouseId = null)
    {
        if (isset($languageHouseId)) {
            $person['organization'] = '/organizations/' . $languageHouseId;
        } else {
            $person = [];
        }
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

    private function getPersonPropertiesFromContactDetails(array $person, array $contactDetails): array
    {
        //todo: check in StudentService -> checkStudentValues() if other is chosen, if so make sure an other option is given (see learningNeed)
        if (isset($contactDetails['contactPreference'])) {
            $person['contactPreference'] = $contactDetails['contactPreference'];
        } elseif ($contactDetails['contactPreferenceOther']) {
            $person['contactPreference'] = $contactDetails['contactPreferenceOther'];
        }

        return $person;
    }

    private function getPersonPropertiesFromGeneralDetails(array $person, array $generalDetails): array
    {
        // todo:birthplace is an cc/address not an string!
//        if (isset($generalDetails['countryOfOrigin'])) {
//            $person['birthplace'] = $generalDetails['countryOfOrigin'];
//        }
        //todo check in StudentService -> checkStudentValues() if this is a iso country code (NL)
        if (isset($generalDetails['nativeLanguage'])) {
            $person['primaryLanguage'] = $generalDetails['nativeLanguage'];
        }
        // todo:must be an array, convert string to an array
//        if (isset($generalDetails['otherLanguages'])) {
//            $person['speakingLanguages'] = $generalDetails['otherLanguages'];
//        }
        //todo: check in StudentService -> checkStudentValues() if this is one of the enum values ("MARRIED_PARTNER","SINGLE","DIVORCED","WIDOW")
        //todo: needs to update taalhuizen CC for update enum options to match these options ^
//        if (isset($generalDetails['familyComposition'])) {
//            $person['maritalStatus'] = $generalDetails['familyComposition'];
//        }
        //todo: loop through dates in a string, not an array!
//        if (isset($generalDetails['childrenDatesOfBirth'])) {
//            foreach ($generalDetails['childrenDatesOfBirth'] as $child) {
//                $person['dependents'][] = $child;
//            }
//        }

        return $person;
    }

    private function getPersonPropertiesFromBackgroundDetails(array $person, array $backgroundDetails): array
    {
        //todo: check in StudentService -> checkStudentValues() for enum options and if other is chosen make sure an other option is given (see learningNeed)
        // (VOLUNTEER_CENTER, LIBRARY_WEBSITE, SOCIAL_MEDIA, NEWSPAPER, VIA_VIA, OTHER)
        if (isset($backgroundDetails['foundVia'])) {
            $person['foundVia'] = $backgroundDetails['foundVia'];
        } elseif (isset($backgroundDetails['foundViaOther'])) {
            $person['foundVia'] = $backgroundDetails['foundViaOther'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBefore'])) {
            $person['wentToTaalhuisBefore'] = (bool)$backgroundDetails['wentToTaalhuisBefore'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBeforeReason'])) {
            $person['wentToTaalhuisBeforeReason'] = $backgroundDetails['wentToTaalhuisBeforeReason'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBeforeYear'])) {
            $person['wentToTaalhuisBeforeYear'] = $backgroundDetails['wentToTaalhuisBeforeYear'];
        }
        //todo: check in StudentService -> checkStudentValues() if all values in this array are one of the enum values
        // (HOUSEHOLD_MEMBERS, NEIGHBORS, FAMILY_MEMBERS, AID_WORKERS, FRIENDS_ACQUAINTANCES, PEOPLE_AT_MOSQUE_CHURCH, ACQUAINTANCES_SPEAKING_OWN_LANGUAGE, ACQUAINTANCES_SPEAKING_DUTCH)
        if (isset($backgroundDetails['network'])) {
            $person['network'] = $backgroundDetails['network'];
        }
        if (isset($backgroundDetails['participationLadder'])) {
            $person['participationLadder'] = (int)$backgroundDetails['participationLadder'];
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
            $person['knowsLatinAlphabet'] = (bool)$dutchNTDetails['knowsLatinAlphabet'];
        }
        if (isset($dutchNTDetails['lastKnownLevel'])) {
            $person['lastKnownLevel'] = $dutchNTDetails['lastKnownLevel'];
        }

        return $person;
    }

    private function getPersonPropertiesFromAvailabilityDetails(array $person, array $availabilityDetails)
    {
        if (isset($availabilityDetails['availability'])) {
            $person['availability'] = $availabilityDetails['availability'];
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

    private function inputToParticipant(array $input, string $ccPersonUrl = null, string $languageHouseUrl = null): array
    {
        // Add cc/person to this edu/participant
        if (isset($ccPersonUrl)) {
            $participant['person'] = $ccPersonUrl;
        } else {
            $participant = [];
        }

        $participant['status'] = 'accepted';

        if (isset($languageHouseUrl)) {
            $programs = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'programs'], ['provider' => $languageHouseUrl])['hydra:member'];
            if (count($programs) > 0) {
                $participant['program'] = '/programs/' . $programs[0]['id'];
            } else {
                throw new Exception('Invalid request, ' . $languageHouseUrl . ' does not have an existing program (edu/program)!');
            }
        }


        //todo: convert 2 functions below to this one... >>>

        return $participant;
    }

    //todo: replace to other function ^ inputToParticipant saving resources should be done in createStudent, updateStudent or actually even in StudentService
    private function saveParticipant(array $input, string $ccPersonUrl, string $languageHouseUrl)
    {
        if (isset($ccPersonUrl)) {
            $participant = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], ['person' => $ccPersonUrl])['hydra:member'][0];
            $participant = $this->EAVService->getObject('participants', null, 'edu', $participant['id']);
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

        //todo: this not here?:
//        $participant = $this->studentService->saveStudentResource($participant, 'participants');

        return $participant['id'];
    }

    //todo: replace to other function ^ inputToParticipant saving resources should be done in createStudent, updateStudent or actually even in StudentService
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
            //todo: this not here?:
//            $referringOrganization = $this->commonGroundService->saveResource($referringOrganization, ['component' => 'cc', 'type' => 'organizations', 'id' => $referringOrganization['id']]);
        } else {
            //todo: this not here?:
//            $referringOrganization = $this->commonGroundService->saveResource($referringOrganization, ['component' => 'cc', 'type' => 'organizations']);
        }
        if (isset($referrerDetails['email'])) {
            if (isset($referringOrganization['emails'][0])) {
                $referringOrganization['emails'][0]['email'] = $referrerDetails['email'];
                //todo: this not here?:
//                $email = $this->commonGroundService->saveResource($referringOrganization['emails'][0], $referringOrganization['emails'][0]['@id']);
            } else {
                $email['name'] = 'Email ' . $referringOrganization['name'];
                $email['email'] = $referrerDetails['email'];
                $email['organization'] = '/organization/' . $referringOrganization['id'];
                //todo: this not here?:
//                $email = $this->commonGroundService->saveResource($email, ['component' => 'cc', 'type' => 'emails']);
            }
            $referringOrganization['emails'][0] = '/emails/' . $email['id'];
        }

        $participant['referredBy'] = $referringOrganization['@id'];

        return $participant;
    }

    private function inputToEmployee($input, $personUrl): array
    {
        $employee = [
            'person' => $personUrl
        ];
        if (isset($input['educationDetails'])) {
            $employee = $this->getEmployeePropertiesFromEducationDetails($employee, $input['educationDetails']);
        }
        if (isset($input['courseDetails'])) {
            $employee = $this->getEmployeePropertiesFromCourseDetails($employee, $input['courseDetails']);
        }
        if (isset($input['jobDetails'])) {
            $employee = $this->getEmployeePropertiesFromJobDetails($employee, $input['jobDetails']);
        }

        return $employee;
    }

    private function getEmployeePropertiesFromEducationDetails(array $employee, array $educationDetails): array
    {
        $newEducation = [
            'name' => 'lastEducation',
            'description' => 'lastEducation'
        ];
        if (isset($educationDetails['lastFollowedEducation'])) {
            $newEducation = [
                'name' => $educationDetails['lastFollowedEducation'],
                'iscedEducationLevelCode' => $educationDetails['lastFollowedEducation']
            ];
            if (isset($educationDetails['didGraduate'])) {
                if ($educationDetails['didGraduate'] == true) {
                    $newEducation['degreeGrantedStatus'] = 'Granted';
                } else {
                    $newEducation['degreeGrantedStatus'] = 'notGranted';
                }
            }
        }
        $employee['educations'][] = $newEducation;

        $newEducation = [
            'name' => 'followingEducation',
            'description' => 'followingEducation'
        ];
        if (isset($educationDetails['followingEducationRightNow'])) {
            if ($educationDetails['followingEducationRightNow'] == 'YES') {
                if (isset($educationDetails['followingEducationRightNowYesStartDate'])) {
                    $newEducation['startDate'] = $educationDetails['followingEducationRightNowYesStartDate'];
                }
                if (isset($educationDetails['followingEducationRightNowYesEndDate'])) {
                    $newEducation['endDate'] = $educationDetails['followingEducationRightNowYesEndDate'];
                }
                if (isset($educationDetails['followingEducationRightNowYesLevel'])) {
                    $newEducation['name'] = $educationDetails['followingEducationRightNowYesLevel'];
                    $newEducation['iscedEducationLevelCode'] = $educationDetails['followingEducationRightNowYesLevel'];
                }
                if (isset($educationDetails['followingEducationRightNowYesInstitute'])) {
                    $newEducation['institution'] = $educationDetails['followingEducationRightNowYesInstitute'];
                }
                if (isset($educationDetails['followingEducationRightNowYesProvidesCertificate'])) {
                    if ($educationDetails['followingEducationRightNowYesProvidesCertificate'] == true) {
                        $newEducation['providesCertificate'] = true;
                    } else {
                        $newEducation['providesCertificate'] = false;
                    }
                }
            } else {
                if (isset($educationDetails['followingEducationRightNowNoEndDate'])) {
                    $newEducation['endDate'] = $educationDetails['followingEducationRightNowNoEndDate'];
                }
                if (isset($educationDetails['followingEducationRightNowNoLevel'])) {
                    $newEducation['name'] = $educationDetails['followingEducationRightNowNoLevel'];
                    $newEducation['iscedEducationLevelCode'] = $educationDetails['followingEducationRightNowNoLevel'];
                }
                if (isset($educationDetails['followingEducationRightNowNoGotCertificate'])) {
                    if ($educationDetails['followingEducationRightNowNoGotCertificate'] == true) {
                        $newEducation['degreeGrantedStatus'] = 'Granted';
                    } else {
                        $newEducation['degreeGrantedStatus'] = 'notGranted';
                    }
                }
            }
        }
        $employee['educations'][] = $newEducation;

        return $employee;
    }

    private function getEmployeePropertiesFromCourseDetails(array $employee, array $courseDetails): array
    {
        $newEducation = [
            'name' => 'course',
            'description' => 'course'
        ];
        if (isset($courseDetails['isFollowingCourseRightNow'])) {
            if ($courseDetails['isFollowingCourseRightNow'] == true) {
                if (isset($courseDetails['courseName'])) {
                    $newEducation['name'] = $courseDetails['courseName'];
                }
                if (isset($courseDetails['courseTeacher'])) {
                    $newEducation['teacherProfessionalism'] = $courseDetails['courseTeacher'];
                }
                if (isset($courseDetails['courseGroup'])) {
                    $newEducation['groupFormation'] = $courseDetails['courseGroup'];
                }
                if (isset($courseDetails['amountOfHours'])) {
                    $newEducation['amountOfHours'] = $courseDetails['amountOfHours'];
                }
                if (isset($courseDetails['doesCourseProvideCertificate'])) {
                    if ($courseDetails['doesCourseProvideCertificate'] == true) {
                        $newEducation['providesCertificate'] = true;
                    } else {
                        $newEducation['providesCertificate'] = false;
                    }
                }
            }
        }
        $employee['educations'][] = $newEducation;

        return $employee;
    }

    private function getEmployeePropertiesFromJobDetails($employee, $jobDetails): array
    {
        if (isset($jobDetails['trainedForJob'])) {
            $employee['trainedForJob'] = $jobDetails['trainedForJob'];
        }
        if (isset($jobDetails['lastJob'])) {
            $employee['lastJob'] = $jobDetails['lastJob'];
        }
        if (isset($jobDetails['dayTimeActivities'])) {
            $employee['dayTimeActivities'] = $jobDetails['dayTimeActivities'];
        }
        if (isset($jobDetails['dayTimeActivitiesOther'])) {
            $employee['dayTimeActivitiesOther'] = $jobDetails['dayTimeActivitiesOther'];
        }

        return $employee;
    }
}
