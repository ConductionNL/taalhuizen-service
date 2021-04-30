<?php

namespace App\Service;

use App\Entity\Student;
use App\Service\EAVService;
use App\Service\CCService;
use App\Service\EDUService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class StudentService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private CCService $ccService;
    private EDUService $eduService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, EAVService $eavService, CCService $ccService, EDUService $eduService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
        $this->ccService = $ccService;
        $this->eduService = $eduService;
    }

    public function saveStudent(array $person, array $participant, $languageHouseId = null, $languageHouseUrl = null) {
        // todo use this to create and update the cc/person, edu/participant etc. instead of in the resolver

        if (isset($languageHouseId)) {
            $person['organization'] = '/organizations/' . $languageHouseId;
        }
//        $person = $this->ccService->saveEavPerson($person);

        $participant['person'] = $person['@id'];
//        $participant = $this->eduService->saveEavParticipant($participant);

        //todo: same for mrc and memo objects...

        return [
            'person' => $person,
            'participant' => $participant
        ];
    }

    // todo:
    public function deleteStudent($id) {
//        if ($this->eavService->hasEavObject(null, 'students', $id)) {
//            $result['participants'] = [];
//            // Get the student from EAV
//            $student = $this->eavService->getObject('students', null, 'eav', $id);
//
//            // Remove this student from all EAV/edu/participants
//            foreach ($student['participants'] as $studentUrl) {
//                $studentResult = $this->removeStudentFromStudent($student['@eav'], $studentUrl);
//                if (isset($studentResult['participant'])) {
//                    // Add $studentUrl to the $result['participants'] because this is convenient when testing or debugging (mostly for us)
//                    array_push($result['participants'], $studentResult['participant']['@id']);
//                }
//            }
//
//            // Delete the student in EAV
//            $this->eavService->deleteObject($student['eavId']);
//            // Add $student to the $result['student'] because this is convenient when testing or debugging (mostly for us)
//            $result['student'] = $student;
//        } else {
//            $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/student!';
//        }
//        return $result;
    }

    public function getStudent($id): array
    {
        $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id]);
        if (!$this->commonGroundService->isResource($studentUrl)) {
            throw new Exception('Invalid request, studentId is not an existing student (edu/participant)!');
        }

        // Get the edu/participant from EAV
        if ($this->eavService->hasEavObject($studentUrl)) {
            $participant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            $result['participant'] = $participant;

            if (!$this->commonGroundService->isResource($participant['person'])) {
                throw new Exception('Warning, '. $participant['person'] .' the person (cc/person) of this student does not exist!');
            }
            // Get the cc/person from EAV
            if ($this->eavService->hasEavObject($participant['person'])) {
                $person = $this->eavService->getObject('people', $participant['person'], 'cc');
                $result['person'] = $person;
            } else {
                throw new Exception('Warning, '. $participant['person'] .' does not have an eav object (eav/cc/people)!');
            }
        } else {
            throw new Exception('Invalid request, '. $id .' is not an existing student (eav/edu/participant)!');
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getStudents($languageHouseId): array
    {
        // Get the edu/participants from EAV
        $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        if ($this->commonGroundService->isResource($languageHouseUrl)) {
            // check if this taalhuis has an edu/program and get it
            $programs = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'programs'], ['provider' => $languageHouseUrl])['hydra:member'];
            if (count($programs) > 0) {
                $students = [];
                foreach ($programs[0]['participants'] as $student) {
                    array_push($students, $this->getStudent($student['id']));
                }
            } else {
                throw new Exception('Invalid request, '. $languageHouseId .' does not have an existing program (edu/program)!');
            }
        } else {
            throw new Exception('Invalid request, '. $languageHouseId .' is not an existing taalhuis (cc/organization)!');
        }
        return $students;
    }

    public function checkStudentValues($input, $languageHouseUrl = null) {
        if (isset($languageHouseUrl) and !$this->commonGroundService->isResource($languageHouseUrl)) {
            throw new Exception('Invalid request, languageHouseId is not an existing cc/organization!');
        }

        // todo: make sure every subresource json array from the input follows the rules (required, enums, etc) from the corresponding entities!
        $personDetails = $input['personDetails'];
        if (isset($personDetails['gender']) && $personDetails['gender'] != 'Male' && $personDetails['gender'] != 'Female') {
            throw new Exception('Invalid request, personDetails gender: the selected value is not a valid option [Male, Female]');
        }
        if (isset($personDetails['dateOfBirth'])) {
            try {
                new \DateTime($personDetails['dateOfBirth']);
            } catch (Exception $e) {
                throw new Exception('Invalid request, personDetails dateOfBirth: failed to parse String to DateTime');
            }
        }

        // todo: etc...
//        $personDetails = $input['personDetails'];
//        if (isset($personDetails['gender']) && $personDetails['gender'] != 'Male' && $personDetails['gender'] != 'Female') {
//            throw new Exception('Invalid request, personDetails gender: the selected value is not a valid option [Male, Female]');
//        }
    }

    public function handleResult($person, $participant) {
        // Put together the expected result for Lifely:
        $resource = new Student();

        //todo:make sure to get all data from the correct places
        // all variables are checked from the $person right now, this should and could be $participant or $employee in some places!

        // Create all subresources
        $civicIntegrationDetails = [
            'civicIntegrationRequirement' => $person['civicIntegrationRequirement'] ?? null,
            'civicIntegrationRequirementReason' => $person['civicIntegrationRequirementReason'] ?? null,
            'civicIntegrationRequirementFinishDate' => $person['civicIntegrationRequirementFinishDate'] ?? null,
        ];

        $personDetails = [
            'givenName' => $person['givenName'] ?? null,
            'additionalName' => $person['additionalName'] ?? null,
            'familyName' => $person['familyName'] ?? null,
            'gender' => $person['gender'] ?? null,
            'birthday' => $person['birthday'] ?? null,
        ];

        $contactDetails = [
            'street' => $person['addresses'][0]['street'] ?? null,
            'postalCode' => $person['addresses'][0]['postalCode'] ?? null,
            'locality' => $person['addresses'][0]['locality'] ?? null,
            'houseNumber' => $person['addresses'][0]['houseNumber'] ?? null,
            'houseNumberSuffix' => $person['addresses'][0]['houseNumberSuffix'] ?? null,
            'email' => $person['emails'][0]['email'] ?? null,
            'telephone' => $person['telephones'][0]['telephone'] ?? null,
            'contactPersonTelephone' => $person['telephones'][1]['telephone'] ?? null,
            'contactPreference' => $person['contactPreference'] ?? null,
            'contactPreferenceOther' => $person['contactPreference'] ?? null,
            //todo does not check for contactPreferenceOther isn't saved separately right now
        ];

        $generalDetails = [
            'birthplace' => $person['birthplace'] ?? null,
            'nativeLanguage' => $person['primaryLanguage'] ?? null,
            'otherLanguages' => $person['speakingLanguages'] ?? null,
            'familyComposition' => $person['maritalStatus'] ?? null,
            'childrenDatesOfBirth' => $person['dependents'] ?? null,
        ];

        $referrerDetails = [
            'referringOrganization' => $person['referringOrganization'] ?? null,
            'referringOrganizationOther' => $person['referringOrganizationOther'] ?? null,
            'email' => $person['email'] ?? null,
        ];

        $backgroundDetails = [
            'foundVia' => $person['foundVia'] ?? null,
            'foundViaOther' => $person['foundVia'] ?? null,
            //todo does not check for foundViaOther^ isn't saved separately right now
            'wentToTaalhuisBefore' => isset($person['wentToTaalhuisBefore']) ? (bool)$person['wentToTaalhuisBefore'] : null,
            'wentToTaalhuisBeforeReason' => $person['wentToTaalhuisBeforeReason'] ?? null,
            'wentToTaalhuisBeforeYear' => $person['wentToTaalhuisBeforeYear'] ?? null,
            'network' => $person['network'] ?? null,
            'participationLadder' => isset($person['participationLadder']) ? (int)$person['participationLadder'] : null,
        ];

        $dutchNTDetails = [
            'dutchNTLevel' => $person['dutchNTLevel'] ?? null,
            'inNetherlandsSinceYear' => $person['inNetherlandsSinceYear'] ?? null,
            'languageInDailyLife' => $person['languageInDailyLife'] ?? null,
            'knowsLatinAlphabet' => isset($person['knowsLatinAlphabet']) ? (bool)$person['knowsLatinAlphabet'] : null,
            'lastKnownLevel' => $person['lastKnownLevel'] ?? null,
        ];

        $educationDetails = [
            'lastFollowedEducation' => $person['lastFollowedEducation'] ?? null,
            'didGraduate' => $person['didGraduate'] ?? null,
            'followingEducationRightNow' => $person['followingEducationRightNow'] ?? null,
            'followingEducationRightNowYesStartDate' => $person['followingEducationRightNowYesStartDate'] ?? null,
            'followingEducationRightNowYesEndDate' => $person['followingEducationRightNowYesEndDate'] ?? null,
            'followingEducationRightNowYesLevel' => $person['followingEducationRightNowYesLevel'] ?? null,
            'followingEducationRightNowYesInstitute' => $person['followingEducationRightNowYesInstitute'] ?? null,
            'followingEducationRightNowYesProvidesCertificate' => $person['followingEducationRightNowYesProvidesCertificate'] ?? null,
            'followingEducationRightNowNoEndDate' => $person['followingEducationRightNowNoEndDate'] ?? null,
            'followingEducationRightNowNoLevel' => $person['followingEducationRightNowNoLevel'] ?? null,
            'followingEducationRightNowNoGotCertificate' => $person['followingEducationRightNowNoGotCertificate'] ?? null,
        ];

        $courseDetails = [
            'isFollowingCourseRightNow' => $person['isFollowingCourseRightNow'] ?? null,
            'courseName' => $person['courseName'] ?? null,
            'courseTeacher' => $person['courseTeacher'] ?? null,
            'courseGroup' => $person['courseGroup'] ?? null,
            'amountOfHours' => $person['amountOfHours'] ?? null,
            'doesCourseProvideCertificate' => $person['doesCourseProvideCertificate'] ?? null,
        ];

        $jobDetails = [
            'trainedForJob' => $person['trainedForJob'] ?? null,
            'lastJob' => $person['lastJob'] ?? null,
            'dayTimeActivities' => $person['dayTimeActivities'] ?? null,
            'dayTimeActivitiesOther' => $person['dayTimeActivitiesOther'] ?? null,
        ];

        $motivationDetails = [
            'desiredSkills' => $person['desiredSkills'] ?? null,
            'desiredSkillsOther' => $person['desiredSkillsOther'] ?? null,
            'hasTriedThisBefore' => $person['hasTriedThisBefore'] ?? null,
            'hasTriedThisBeforeExplanation' => $person['hasTriedThisBeforeExplanation'] ?? null,
            'whyWantTheseskills' => $person['whyWantTheseskills'] ?? null,
            'whyWantThisNow' => $person['whyWantThisNow'] ?? null,
            'desiredLearningMethod' => $person['desiredLearningMethod'] ?? null,
            'remarks' => $person['remarks'] ?? null,
        ];

        $availabilityDetails = [
            'availability' => $person['availability'] ?? null,
        ];

        $permissionDetails = [
            'didSignPermissionForm' => $person['didSignPermissionForm'] ?? null,
            'hasPermissionToShareDataWithAanbieders' => $person['hasPermissionToShareDataWithAanbieders'] ?? null,
            'hasPermissionToShareDataWithLibraries' => $person['hasPermissionToShareDataWithLibraries'] ?? null,
            'hasPermissionToSendInformationAboutLibraries' => $person['hasPermissionToSendInformationAboutLibraries'] ?? null,
        ];

        // Set all subresources in response DTO body
        if (isset($participant['dateCreated'])) { $resource->setDateCreated(new \DateTime($participant['dateCreated'])); } //todo: this is currently incorrect, timezone problem
        $resource->setStatus(null);
        $resource->setMemo(null);
        $resource->setRegistrar([]);
        $resource->setCivicIntegrationDetails($civicIntegrationDetails);
        $resource->setPersonDetails($personDetails);
        $resource->setContactDetails($contactDetails);
        $resource->setGeneralDetails($generalDetails);
        $resource->setReferrerDetails($referrerDetails);
        $resource->setBackgroundDetails($backgroundDetails);
        $resource->setDutchNTDetails($dutchNTDetails);
        if (isset($person['speakingLevel'])) { $resource->setSpeakingLevel($person['speakingLevel']); }
        $resource->setEducationDetails($educationDetails);
        $resource->setCourseDetails($courseDetails);
        $resource->setJobDetails($jobDetails);
        $resource->setMotivationDetails($motivationDetails);
        $resource->setAvailabilityDetails($availabilityDetails);
        if (isset($person['readingTestResult'])) { $resource->setReadingTestResult($person['readingTestResult']); }
        if (isset($person['writingTestResult'])) { $resource->setWritingTestResult($person['writingTestResult']); }
        $resource->setPermissionDetails($permissionDetails);

        // For some reason setting the id does not work correctly when done inside this function, so do it after calling this handleResult function instead!
//        $resource->setId(Uuid::getFactory()->fromString($participant['id']));
        $this->entityManager->persist($resource);
        return $resource;
    }
}
