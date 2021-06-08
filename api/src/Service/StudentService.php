<?php

namespace App\Service;

use App\Entity\Registration;
use App\Entity\Student;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
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
    private MrcService $mrcService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        EAVService $eavService,
        CCService $ccService,
        EDUService $eduService,
        MrcService $mrcService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
        $this->ccService = $ccService;
        $this->eduService = $eduService;
        $this->mrcService = $mrcService;
    }

    public function saveStudent(array $person, array $participant, $languageHouseId = null, $languageHouseUrl = null)
    {
        // todo use this to create and update the cc/person, edu/participant etc. instead of in the resolver

        if (isset($languageHouseId)) {
            $person['organization'] = '/organizations/'.$languageHouseId;
        }

        $participant['person'] = $person['@id'];

        //todo: same for mrc and memo objects...

        return [
            'person'      => $person,
            'participant' => $participant,
        ];
    }

    public function deleteStudent($id)
    {
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

    /**
     * @throws Exception
     */
    public function getStudent($id, $studentUrl = null, $skipChecks = false): array
    {
        if (isset($id)) {
            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id]);
        }
        if (!$skipChecks && !$this->commonGroundService->isResource($studentUrl)) {
            throw new Exception('Invalid request, studentId is not an existing student (edu/participant)!');
        }

        // Get the edu/participant from EAV
        if ($skipChecks || $this->eavService->hasEavObject($studentUrl)) {
            $result = $this->getStudentObjects($studentUrl, $skipChecks);
        } else {
            throw new Exception('Invalid request, '.$id.' is not an existing student (eav/edu/participant)!');
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private function getStudentObjects($studentUrl = null, $skipChecks = false): array
    {
        $participant = $this->eavService->getObject('participants', $studentUrl, 'edu');

        $person = $this->getStudentPerson($participant, $skipChecks);

        // get the memo for availabilityNotes and add it to the $person
        if (isset($person)) {
            $person = $this->getStudentAvailabilityNotes($person);
        }

        // get the memo for remarks (motivationDetails) and add it to the $participant
        if (isset($participant)) {
            $participant = $this->getStudentMotivationDetailsRemarks($person, $participant);
        }

        // get the registrarOrganization, registrarPerson and its memo
        if (isset($participant['referredBy'])) {
            $registrarOrganization = $this->commonGroundService->getResource($participant['referredBy']);
            if (isset($registrarOrganization['persons'][0]['@id'])) {
                $registrarPerson = $this->commonGroundService->getResource($registrarOrganization['persons'][0]['@id']);
            }
            $registrarMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['topic' => $person['@id'], 'author' => $registrarOrganization['@id']])['hydra:member'];
            if (count($registrarMemos) > 0) {
                $registrarMemo = $registrarMemos[0];
            }
        }

        // Get students data from mrc
        $employee = $this->getStudentEmployee($person, $skipChecks);

        return [
            'participant'           => $participant ?? null,
            'person'                => $person ?? null,
            'employee'              => $employee ?? null,
            'registrarOrganization' => $registrarOrganization ?? null,
            'registrarPerson'       => $registrarPerson ?? null,
            'registrarMemo'         => $registrarMemo ?? null,
        ];
    }

    private function getStudentPerson(array $participant, $skipChecks = false): array
    {
        if (!$skipChecks && !$this->commonGroundService->isResource($participant['person'])) {
            throw new Exception('Warning, '.$participant['person'].' the person (cc/person) of this student does not exist!');
        }
        // Get the cc/person from EAV
        if ($skipChecks || $this->eavService->hasEavObject($participant['person'])) {
            $person = $this->eavService->getObject('people', $participant['person'], 'cc');
        } else {
            throw new Exception('Warning, '.$participant['person'].' does not have an eav object (eav/cc/people)!');
        }
        return $person;
    }

    private function getStudentAvailabilityNotes(array $person): array
    {
        //todo: also use author as filter, for this: get participant->program->provider (= languageHouseUrl when this memo was created)
        $availabilityMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['name' => 'Availability notes', 'topic' => $person['@id']])['hydra:member'];
        if (count($availabilityMemos) > 0) {
            $availabilityMemo = $availabilityMemos[0];
            $person['availabilityNotes'] = $availabilityMemo['description'];
        }

        return $person;
    }

    private function getStudentMotivationDetailsRemarks(array $person, array $participant): array
    {
        //todo: also use author as filter, for this: get participant->program->provider (= languageHouseUrl when this memo was created)
        $motivationMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['name' => 'Remarks', 'topic' => $person['@id']])['hydra:member'];
        if (count($motivationMemos) > 0) {
            $motivationMemo = $motivationMemos[0];
            $participant['remarks'] = $motivationMemo['description'];
        }

        return $participant;
    }

    private function getStudentEmployee(array $person, $skipChecks = false): array
    {
        $employees = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['person' => $person['@id']])['hydra:member'];
        if (count($employees) > 0) {
            $employee = $employees[0];
            if ($skipChecks || $this->eavService->hasEavObject($employee['@id'])) {
                $employee = $this->eavService->getObject('employees', $employee['@id'], 'mrc');
            }
        }

        return $employee;
    }

    /**
     * @param array $query
     *
     * @return array
     */
    public function getStudents(array $query, bool $registrations = false): array
    {
        $students = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], $query)['hydra:member'];
        foreach ($students as $key => $student) {
            if ($registrations and !isset($student['referredBy'])) {
                continue;
            }
            $students[$key] = $this->getStudent($student['id']);
        }

        return $students;
    }

    /**
     * @throws Exception
     */
    public function getStudentsWithStatus($providerId, $status): ArrayCollection
    {
        $collection = new ArrayCollection();
        // Check if provider exists in eav and get it if it does
        if ($this->eavService->hasEavObject(null, 'organizations', $providerId, 'cc')) {
            $providerUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $providerId]);
            $provider = $this->eavService->getObject('organizations', $providerUrl, 'cc');
            // Get the provider eav/cc/organization participations and their edu/participant urls from EAV
            $studentUrls = [];
            foreach ($provider['participations'] as $participationUrl) {
                try {
                    //todo: do hasEavObject checks here? For now removed because it will slow down the api call if we do to many calls in a foreach
//                    if ($this->eavService->hasEavObject($participationUrl)) {
                    // Get eav/Participation
                    $participation = $this->eavService->getObject('participations', $participationUrl);
                    //after isset add && hasEavObject? $this->eavService->hasEavObject($participation['learningNeed']) todo: same here?
                    if ($participation['status'] == $status && isset($participation['learningNeed'])) {
                        //maybe just add the edu/participant (/student) url to the participation as well, to do one less call (this one:) todo?
                        // Get eav/LearningNeed
                        $learningNeed = $this->eavService->getObject('learning_needs', $participation['learningNeed']);
                        if (isset($learningNeed['participants']) && count($learningNeed['participants']) > 0) {
                            // Add studentUrl to array, if it is not already in there
                            if (!in_array($learningNeed['participants'][0], $studentUrls)) {
                                $studentUrls[] = $learningNeed['participants'][0];
                                // Get the actual student, use skipChecks=true in order to reduce the amount of calls used
                                $student = $this->getStudent(null, $learningNeed['participants'][0], true);
                                if ($student['participant']['status'] == 'accepted') {
                                    // Handle Result
                                    $resourceResult = $this->handleResult($student['person'], $student['participant'], $student['employee']);
                                    $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
                                    // Add to the collection
                                    $collection->add($resourceResult);
                                }
                            }
                        }
                    }
//                        else {
//                            $result['message'] = 'Warning, '. $participation['learningNeed'] .' is not an existing eav/learning_need!';
//                        }
//                    } else {
//                        $result['message'] = 'Warning, '. $participationUrl .' is not an existing eav/participation!';
//                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        } else {
            // Do not throw an error, because we want to return an empty array in this case
            $result['message'] = 'Warning, '.$providerId.' is not an existing eav/cc/organization!';
        }

        return $collection;
    }

    public function checkStudentValues($input, $languageHouseUrl = null)
    {
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

    public function handleResult($person, $participant, $employee, $registrarPerson = null, $registrarOrganization = null, $registrarMemo = null, $registration = null)
    {
        // Put together the expected result for Lifely:
        if (isset($registration)) {
            $resource = new Registration();
        } else {
            $resource = new Student();
        }

        // Set all subresources in response DTO body
        $resource = $this->handleSubResources($resource, $person, $participant, $employee, $registrarPerson, $registrarOrganization, $registration);

        if (isset($participant['dateCreated'])) {
            $resource->setDateCreated(new \DateTime($participant['dateCreated']));
        } //todo: this is currently incorrect, timezone problem
        if (isset($participant['status'])) {
            $resource->setStatus($participant['status']);
        }
        if (isset($registrarMemo['description'])) {
            $resource->setMemo($registrarMemo['description']);
        }
        if (isset($employee['speakingLevel'])) {
            $resource->setSpeakingLevel($employee['speakingLevel']);
        }
        if (isset($participant['readingTestResult'])) {
            $resource->setReadingTestResult($participant['readingTestResult']);
        }
        if (isset($participant['writingTestResult'])) {
            $resource->setWritingTestResult($participant['writingTestResult']);
        }
        $this->entityManager->persist($resource);

        return $resource;
    }

    private function handleSubResources($resource, $person, $participant, $employee, $registrarPerson = null, $registrarOrganization = null, $registration = null): object
    {
        $resource->setRegistrar($this->handleRegistrar($registrarPerson, $registrarOrganization));
        $resource->setCivicIntegrationDetails($this->handleCivicIntegrationDetails($person));
        $resource->setPersonDetails($this->handlePersonDetails($person));
        $resource->setContactDetails($this->handleContactDetails($person));
        $resource->setGeneralDetails($this->handleGeneralDetails($person));
        $resource->setReferrerDetails($this->handleReferrerDetails($registration, $participant, $registrarPerson, $registrarOrganization));
        $resource->setBackgroundDetails($this->handleBackgroundDetails($person));
        $resource->setDutchNTDetails($this->handleDutchNTDetails($person));

        $mrcEducations = $this->getEducationsFromEmployee($employee);
        $resource->setEducationDetails($this->handleEducationDetails($mrcEducations['lastEducation'], $mrcEducations['followingEducationYes'], $mrcEducations['followingEducationNo']));
        $resource->setCourseDetails($this->handleCourseDetails($mrcEducations['course']));
        $resource->setJobDetails($this->handleJobDetails($employee));
        $resource->setMotivationDetails($this->handleMotivationDetails($participant));
        $resource->setAvailabilityDetails($this->handleAvailabilityDetails($person));
        $resource->setPermissionDetails($this->handlePermissionDetails($person));

        return $resource;
    }

    private function handleRegistrar($registrarPerson = null, $registrarOrganization = null): array
    {
        return [
            'id'               => $registrarOrganization['id'] ?? null,
            'organisationName' => $registrarOrganization['name'] ?? null,
            'givenName'        => $registrarPerson['givenName'] ?? null,
            'additionalName'   => $registrarPerson['additionalName'] ?? null,
            'familyName'       => $registrarPerson['familyName'] ?? null,
            'email'            => $registrarPerson['telephones'][0]['telephone'] ?? null,
            'telephone'        => $registrarPerson['emails'][0]['email'] ?? null,
        ];
    }

    private function handleCivicIntegrationDetails($person): array
    {
        return [
            'civicIntegrationRequirement'           => $person['civicIntegrationRequirement'] ?? null,
            'civicIntegrationRequirementReason'     => $person['civicIntegrationRequirementReason'] ?? null,
            'civicIntegrationRequirementFinishDate' => $person['civicIntegrationRequirementFinishDate'] ?? null,
        ];
    }

    private function handlePersonDetails($person): array
    {
        return [
            'givenName'      => $person['givenName'] ?? null,
            'additionalName' => $person['additionalName'] ?? null,
            'familyName'     => $person['familyName'] ?? null,
            'gender'         => $person['gender'] ?? null,
            'birthday'       => $person['birthday'] ?? null,
        ];
    }

    private function handleContactDetails($person): array
    {
        return [
            'street'                 => $person['addresses'][0]['street'] ?? null,
            'postalCode'             => $person['addresses'][0]['postalCode'] ?? null,
            'locality'               => $person['addresses'][0]['locality'] ?? null,
            'houseNumber'            => $person['addresses'][0]['houseNumber'] ?? null,
            'houseNumberSuffix'      => $person['addresses'][0]['houseNumberSuffix'] ?? null,
            'email'                  => $person['emails'][0]['email'] ?? null,
            'telephone'              => $person['telephones'][0]['telephone'] ?? null,
            'contactPersonTelephone' => $person['telephones'][1]['telephone'] ?? null,
            'contactPreference'      => $person['contactPreference'] ?? null,
            'contactPreferenceOther' => $person['contactPreference'] ?? null,
            //todo does not check for contactPreferenceOther isn't saved separately right now
        ];
    }

    private function handleGeneralDetails($person): array
    {
        if (isset($person['ownedContactLists'][0]['people']) && $person['ownedContactLists'][0]['name'] == 'Children') {
            $childrenCount = count($person['ownedContactLists'][0]['people']);
            $childrenDatesOfBirth = [];
            foreach ($person['ownedContactLists'][0]['people'] as $child) {
                if (isset($child['birthday'])) {
                    try {
                        $birthday = new \DateTime($child['birthday']);
                        $childrenDatesOfBirth[] = $birthday->format('d-m-Y');
                    } catch (Exception $e) {
                        $childrenDatesOfBirth[] = $child['birthday'];
                    }
                }
            }
        }

        return [
            'countryOfOrigin'      => $person['birthplace']['country'] ?? null,
            'nativeLanguage'       => $person['primaryLanguage'] ?? null,
            'otherLanguages'       => $person['speakingLanguages'] ? implode(',', $person['speakingLanguages']) : null,
            'familyComposition'    => $person['maritalStatus'] ?? null,
            'childrenCount'        => $childrenCount ?? null,
            'childrenDatesOfBirth' => isset($childrenDatesOfBirth) ? implode(',', $childrenDatesOfBirth) : null,
        ];
    }

    private function handleReferrerDetails($registration, $participant, $registrarPerson = null, $registrarOrganization = null): array
    {
        if (isset($registration)) {
            return [
                'referringOrganization'      => $registrarOrganization['name'] ?? null,
                'referringOrganizationOther' => null,
                'email'                      => $registrarPerson['emails'][0]['email'] ?? null,
            ];
        }
        if (!isset($registrarOrganization) && isset($participant['referredBy'])) {
            $registrarOrganization = $this->commonGroundService->getResource($participant['referredBy']);
        }

        return [
            'referringOrganization'      => $registrarOrganization['name'] ?? null,
            'referringOrganizationOther' => $registrarOrganization['name'] ?? null,
            //todo does not check for referringOrganizationOther isn't saved separately right now
            'email' => $registrarOrganization['emails'][0]['email'] ?? null,
        ];
    }

    private function handleBackgroundDetails($person): array
    {
        return [
            'foundVia'      => $person['foundVia'] ?? null,
            'foundViaOther' => $person['foundVia'] ?? null,
            //todo does not check for foundViaOther^ isn't saved separately right now
            'wentToTaalhuisBefore'       => isset($person['wentToTaalhuisBefore']) ? (bool) $person['wentToTaalhuisBefore'] : null,
            'wentToTaalhuisBeforeReason' => $person['wentToTaalhuisBeforeReason'] ?? null,
            'wentToTaalhuisBeforeYear'   => $person['wentToTaalhuisBeforeYear'] ?? null,
            'network'                    => $person['network'] ?? null,
            'participationLadder'        => isset($person['participationLadder']) ? (int) $person['participationLadder'] : null,
        ];
    }

    private function handleDutchNTDetails($person): array
    {
        return [
            'dutchNTLevel'           => $person['dutchNTLevel'] ?? null,
            'inNetherlandsSinceYear' => $person['inNetherlandsSinceYear'] ?? null,
            'languageInDailyLife'    => $person['languageInDailyLife'] ?? null,
            'knowsLatinAlphabet'     => isset($person['knowsLatinAlphabet']) ? (bool) $person['knowsLatinAlphabet'] : null,
            'lastKnownLevel'         => $person['lastKnownLevel'] ?? null,
        ];
    }

    private function getEducationsFromEmployee($employee): array
    {
        $educations = [
            'lastEducation'         => null,
            'followingEducationNo'  => null,
            'followingEducationYes' => null,
            'course'                => null,
        ];

        if (isset($employee['educations'])) {
            foreach ($employee['educations'] as $education) {
                $this->setEducationType($educations, $education);
            }
        }

        return $educations;
    }

    private function setEducationType(&$educations, $education)
    {
        switch ($education['description']) {
            case 'lastEducation':
                $educations['lastEducation'] = $education;
                break;
            case 'followingEducationNo':
                if (!isset($educations['followingEducationYes']) && !isset($educations['followingEducationNo'])) {
                    $educations['followingEducationNo'] = $education;
                }
                break;
            case 'followingEducationYes':
                if (!isset($educations['followingEducationYes']) && !isset($educations['followingEducationNo'])) {
                    $educations['followingEducationYes'] = $this->eavService->getObject('education', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]), 'mrc');
                }
                break;
            case 'course':
                $educations['course'] = $this->eavService->getObject('education', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]), 'mrc');
                break;
        }
    }

    private function handleEducationDetails($lastEducation, $followingEducationYes, $followingEducationNo): array
    {
        return [
            'lastFollowedEducation'                            => $lastEducation['iscedEducationLevelCode'] ?? null,
            'didGraduate'                                      => isset($lastEducation['degreeGrantedStatus']) ? $lastEducation['degreeGrantedStatus'] == 'Granted' : null,
            'followingEducationRightNow'                       => $followingEducationYes ? 'YES' : ($followingEducationNo ? 'NO' : null),
            'followingEducationRightNowYesStartDate'           => $followingEducationYes ? ($followingEducationYes['startDate'] ?? null) : null,
            'followingEducationRightNowYesEndDate'             => $followingEducationYes ? ($followingEducationYes['endDate'] ?? null) : null,
            'followingEducationRightNowYesLevel'               => $followingEducationYes ? ($followingEducationYes['iscedEducationLevelCode'] ?? null) : null,
            'followingEducationRightNowYesInstitute'           => $followingEducationYes ? ($followingEducationYes['institution'] ?? null) : null,
            'followingEducationRightNowYesProvidesCertificate' => $followingEducationYes ? (isset($followingEducationYes['providesCertificate']) ? (bool) $followingEducationYes['providesCertificate'] : null) : null,
            'followingEducationRightNowNoEndDate'              => $followingEducationNo ? ($followingEducationNo['endDate'] ?? null) : null,
            'followingEducationRightNowNoLevel'                => $followingEducationNo ? ($followingEducationNo['iscedEducationLevelCode'] ?? null) : null,
            'followingEducationRightNowNoGotCertificate'       => $followingEducationNo ? $followingEducationNo['degreeGrantedStatus'] == 'Granted' : null,
        ];
    }

    private function handleCourseDetails($course): array
    {
        return [
            'isFollowingCourseRightNow'    => isset($course),
            'courseName'                   => $course['name'] ?? null,
            'courseTeacher'                => $course['teacherProfessionalism'] ?? null,
            'courseGroup'                  => $course['groupFormation'] ?? null,
            'amountOfHours'                => $course['amountOfHours'] ?? null,
            'doesCourseProvideCertificate' => isset($course['providesCertificate']) ? (bool) $course['providesCertificate'] : null,
        ];
    }

    private function handleJobDetails($employee): array
    {
        return [
            'trainedForJob'          => $employee['trainedForJob'] ?? null,
            'lastJob'                => $employee['lastJob'] ?? null,
            'dayTimeActivities'      => $employee['dayTimeActivities'] ?? null,
            'dayTimeActivitiesOther' => $employee['dayTimeActivitiesOther'] ?? null,
        ];
    }

    private function handleMotivationDetails($participant): array
    {
        return [
            'desiredSkills'                 => $participant['desiredSkills'] ?? null,
            'desiredSkillsOther'            => $participant['desiredSkillsOther'] ?? null,
            'hasTriedThisBefore'            => $participant['hasTriedThisBefore'] ?? null,
            'hasTriedThisBeforeExplanation' => $participant['hasTriedThisBeforeExplanation'] ?? null,
            'whyWantTheseSkills'            => $participant['whyWantTheseSkills'] ?? null,
            'whyWantThisNow'                => $participant['whyWantThisNow'] ?? null,
            'desiredLearningMethod'         => $participant['desiredLearningMethod'] ?? null,
            'remarks'                       => $participant['remarks'] ?? null,
        ];
    }

    private function handleAvailabilityDetails($person): array
    {
        return [
            'availability'      => $person['availability'] ?? null,
            'availabilityNotes' => $person['availabilityNotes'] ?? null,
        ];
    }

    private function handlePermissionDetails($person): array
    {
        return [
            'didSignPermissionForm'                        => $person['didSignPermissionForm'] ?? null,
            'hasPermissionToShareDataWithAanbieders'       => $person['hasPermissionToShareDataWithAanbieders'] ?? null,
            'hasPermissionToShareDataWithLibraries'        => $person['hasPermissionToShareDataWithLibraries'] ?? null,
            'hasPermissionToSendInformationAboutLibraries' => $person['hasPermissionToSendInformationAboutLibraries'] ?? null,
        ];
    }
}
