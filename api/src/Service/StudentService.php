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

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = new EAVService($commonGroundService);
    }

    /**
     * This function fetches the student with the given ID.
     *
     * @param string      $id         ID of the student
     * @param string|null $studentUrl URL of the student
     * @param false       $skipChecks Bool if code should skip checks or not
     *
     *@throws Exception
     *
     * @return array Returns student
     */
    public function getStudent(string $id, bool $skipChecks = false): array
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
     * This function fetches a students subresources with the given url.
     *
     * @param string|null $studentUrl URL of the student
     * @param false       $skipChecks Bool if code should skip checks or not
     *
     *@throws Exception
     *
     * @return array Returns array with students subresources
     */
    private function getStudentObjects($studentUrl = null, $skipChecks = false): array
    {
        $participant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);

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
        $registrar = $this->getStudentRegistrar($person, $participant);

        // Get students data from mrc
        $employee = $this->getStudentEmployee($person, $skipChecks);

        return [
            'participant'           => $participant ?? null,
            'person'                => $person ?? null,
            'employee'              => $employee ?? null,
            'registrar'             => $registrar,
        ];
    }

    /**
     * This function fetches the student cc/person.
     *
     * @param array $participant Array with participants data
     * @param false $skipChecks  Bool if code should skip checks or not
     *
     *@throws Exception
     *
     * @return array Returns person as array
     */
    private function getStudentPerson(array $participant, $skipChecks = false): array
    {
        if (!$skipChecks && !$this->commonGroundService->isResource($participant['person'])) {
            throw new Exception('Warning, '.$participant['person'].' the person (cc/person) of this student does not exist!');
        }
        // Get the cc/person from EAV
        if ($skipChecks || $this->eavService->hasEavObject($participant['person'])) {
            $person = $this->eavService->getObject(['entityName' => 'people', 'componentCode' => 'cc', 'self' => $participant['person']]);
        } else {
            throw new Exception('Warning, '.$participant['person'].' does not have an eav object (eav/cc/people)!');
        }

        return $person;
    }

    /**
     * This function fetches the students availability notes.
     *
     * @param array $person Array with persons data
     *
     * @return array Returns person as array
     */
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

    /**
     * This function fetches students motivation details remarks.
     *
     * @param array $person      Array with persons data
     * @param array $participant Array with participants data
     *
     * @return array Returns a participant
     */
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

    /**
     * This function fetches the students registrar.
     *
     * @param array $person
     * @param array $participant
     *
     * @return array Returns array with registrarOrganization, registrarPerson and registrarMemo
     */
    private function getStudentRegistrar(array $person, array $participant): array
    {
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

        return [
            'registrarOrganization' => $registrarOrganization ?? null,
            'registrarPerson'       => $registrarPerson ?? null,
            'registrarMemo'         => $registrarMemo ?? null,
        ];
    }

    /**
     * This function fetches the students employee.
     *
     * @param array $person     Array with persons data
     * @param false $skipChecks Bool if code should skip checks or not
     *
     *@throws Exception
     *
     * @return array|false|mixed|null Returns the employee as array
     */
    private function getStudentEmployee(array $person, $skipChecks = false)
    {
        $employee = null;
        $employees = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['person' => $person['@id']])['hydra:member'];
        if (count($employees) > 0) {
            $employee = $employees[0];
            if ($skipChecks || $this->eavService->hasEavObject($employee['@id'])) {
                $employee = $this->eavService->getObject(['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $employee['@id']]);
            }
        }

        return $employee;
    }

    /**
     * THis function fetches students based on query.
     *
     * @param array $query         Array with query params
     * @param bool  $registrations Bool for if there are registrations
     *
     *@throws Exception
     *
     * @return array Returns an array of students
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
     * This function fetches students with the given status.
     *
     * @param string $providerId ID of the provider
     * @param string $status     A possible status for a student
     *
     *@throws Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection Returns an ArrayCollection with Student objects
     */
    public function getStudentsWithStatus(string $providerId, string $status): ArrayCollection
    {
        $collection = new ArrayCollection();
        // Check if provider exists in eav and get it if it does
        if ($this->eavService->hasEavObject(null, 'organizations', $providerId, 'cc')) {
            $providerUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $providerId]);
            $provider = $this->eavService->getObject(['entityName' => 'organizations', 'componentCode' => 'cc', 'self' => $providerUrl]);
            // Get the provider eav/cc/organization participations and their edu/participant urls from EAV
            $collection = $this->getStudentWithStatusFromParticipations($collection, $provider, $status);
        } else {
            // Do not throw an error, because we want to return an empty array in this case
            $result['message'] = 'Warning, '.$providerId.' is not an existing eav/cc/organization!';
        }

        return $collection;
    }

    /**
     * This function fetches students from participations with the given status.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $collection ArrayCollection that holds participations
     * @param array                                        $provider   Array with providers data
     * @param string                                       $status     A participations status as string
     *
     * @return \Doctrine\Common\Collections\ArrayCollection Returns an ArrayCollection with students
     */
    private function getStudentWithStatusFromParticipations(ArrayCollection $collection, array $provider, string $status): ArrayCollection
    {
        // Get the provider eav/cc/organization participations and their edu/participant urls from EAV
        $studentUrls = [];
        foreach ($provider['participations'] as $participationUrl) {
            try {
                //todo: do hasEavObject checks here? For now removed because it will slow down the api call if we do to many calls in a foreach
//                if ($this->eavService->hasEavObject($participationUrl)) {
                // Get eav/Participation
                $participation = $this->eavService->getObject(['entityName' => 'participations', 'self' => $participationUrl]);
                //after isset add && hasEavObject? $this->eavService->hasEavObject($participation['learningNeed']) todo: same here?
                if ($participation['status'] == $status && isset($participation['learningNeed'])) {
                    $collection = $this->getStudentFromLearningNeed($collection, $studentUrls, $participation['learningNeed']);
                }
//                    else {
//                        $result['message'] = 'Warning, '. $participation['learningNeed'] .' is not an existing eav/learning_need!';
//                    }
//                } else {
//                    $result['message'] = 'Warning, '. $participationUrl .' is not an existing eav/participation!';
//                }
            } catch (Exception $e) {
                continue;
            }
        }

        return $collection;
    }

    /**
     * This function fetches a student from a learning need.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $collection
     * @param array                                        $studentUrls     Array of student urls
     * @param string                                       $learningNeedUrl URL of the learning need
     *
     *@throws Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection Returns an ArrayCollection with a student
     */
    private function getStudentFromLearningNeed(ArrayCollection $collection, array &$studentUrls, string $learningNeedUrl): ArrayCollection
    {
        //maybe just add the edu/participant (/student) url to the participation as well, to do one less call (this one:) todo?
        // Get eav/LearningNeed
        $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'self' => $learningNeedUrl]);
        if (isset($learningNeed['participants']) && count($learningNeed['participants']) > 0) {
            // Add studentUrl to array, if it is not already in there
            if (!in_array($learningNeed['participants'][0], $studentUrls)) {
                $studentUrls[] = $learningNeed['participants'][0];
                // Get the actual student, use skipChecks=true in order to reduce the amount of calls used
                $student = $this->getStudent($this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]), true);
                if ($student['participant']['status'] == 'accepted') {
                    // Handle Result
                    $resourceResult = $this->handleResult($student);
                    $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
                    // Add to the collection
                    $collection->add($resourceResult);
                }
            }
        }

        return $collection;
    }

    /**
     * This function checks if the given student array its data is valid.
     *
     * @param array $input Array with students data
     *
     * @throws Exception
     */
    public function checkStudentValues(array $input)
    {
        if (isset($input['languageHouseUrl']) and !$this->commonGroundService->isResource($input['languageHouseUrl'])) {
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

    /**
     * This function handles the result of a Student object its subresources being set.
     *
     * @param array $student      Array with students data
     * @param false $registration
     *
     * @throws Exception
     *
     * @return object Returns Student object
     */
    public function handleResult(array $student, bool $registration = false): object
    {
        // Put together the expected result for Lifely:
        if ($registration) {
            $resource = new Registration();
        } else {
            $resource = new Student();
        }

        // Set all subresources in response DTO body
        $resource = $this->handleSubResources($resource, $student);

        if (isset($student['participant']['dateCreated'])) {
            $resource->setDateCreated(new \DateTime($student['participant']['dateCreated']));
        } //todo: this is currently incorrect, timezone problem
        if (isset($student['participant']['status'])) {
            $resource->setStatus($student['participant']['status']);
        }
        if (isset($student['registrar']['registrarMemo']['description'])) {
            $resource->setMemo($student['registrar']['registrarMemo']['description']);
        }
        if (isset($student['employee']['speakingLevel'])) {
            $resource->setSpeakingLevel($student['employee']['speakingLevel']);
        }
        if (isset($student['participant']['readingTestResult'])) {
            $resource->setReadingTestResult($student['participant']['readingTestResult']);
        }
        if (isset($student['participant']['writingTestResult'])) {
            $resource->setWritingTestResult($student['participant']['writingTestResult']);
        }
        $this->entityManager->persist($resource);

        return $resource;
    }

    /**
     * This function handles a students subresources being set.
     *
     * @param mixed $resource Student object
     * @param array $student  Array with students data
     *
     * @throws Exception
     *
     * @return object Returns a Student object
     */
    private function handleSubResources($resource, array $student): object
    {
        $resource->setRegistrar($this->handleRegistrar($student['registrar']['registrarPerson'], $student['registrar']['registrarOrganization']));
        $resource->setCivicIntegrationDetails($this->handleCivicIntegrationDetails($student['person']));
        $resource->setPersonDetails($this->handlePersonDetails($student['person']));
        $resource->setContactDetails($this->handleContactDetails($student['person']));
        $resource->setGeneralDetails($this->handleGeneralDetails($student['person']));
        $resource->setReferrerDetails($this->handleReferrerDetails($student['participant'], $student['registrar']['registrarPerson'], $student['registrar']['registrarOrganization']));
        $resource->setBackgroundDetails($this->handleBackgroundDetails($student['person']));
        $resource->setDutchNTDetails($this->handleDutchNTDetails($student['person']));

        if (isset($student['employee'])) {
            $mrcEducations = $this->getEducationsFromEmployee($student['employee']);
            $resource->setEducationDetails($this->handleEducationDetails($mrcEducations['lastEducation'], $mrcEducations['followingEducationYes'], $mrcEducations['followingEducationNo']));
            $resource->setCourseDetails($this->handleCourseDetails($mrcEducations['course']));
            $resource->setJobDetails($this->handleJobDetails($student['employee']));
        }
        $resource->setMotivationDetails($this->handleMotivationDetails($student['participant']));
        $resource->setAvailabilityDetails($this->handleAvailabilityDetails($student['person']));
        $resource->setPermissionDetails($this->handlePermissionDetails($student['person']));

        return $resource;
    }

    /**
     * This function merges a registrarOrganization and registrarPerson in an array.
     *
     * @param null $registrarPerson       Array with registrarPersons data
     * @param null $registrarOrganization Array with registrarOrganizations data
     *
     * @return array|null[] Returns array with registrar data
     */
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

    /**
     * This function passes a persons integration details through an array.
     *
     * @param array $person
     *
     * @return array|null[] Returns an array with integration details
     */
    private function handleCivicIntegrationDetails(array $person): array
    {
        return [
            'civicIntegrationRequirement'           => $person['civicIntegrationRequirement'] ?? null,
            'civicIntegrationRequirementReason'     => $person['civicIntegrationRequirementReason'] ?? null,
            'civicIntegrationRequirementFinishDate' => $person['civicIntegrationRequirementFinishDate'] ?? null,
        ];
    }

    /**
     * This function passes a persons details.
     *
     * @param array $person Array with persons data
     *
     * @return array Returns an array with persons details
     */
    private function handlePersonDetails(array $person): array
    {
        return [
            'givenName'      => $person['givenName'] ?? null,
            'additionalName' => $person['additionalName'] ?? null,
            'familyName'     => $person['familyName'] ?? null,
            'gender'         => $person['gender'] ? $person['gender'] : 'X',
            'birthday'       => $person['birthday'] ?? null,
        ];
    }

    /**
     * This function passes a persons contact details.
     *
     * @param array $person Array with persons data
     *
     * @return array|null[] Returns an array with persons contact details
     */
    private function handleContactDetails(array $person): array
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

    /**
     * This function passes a persons general details.
     *
     * @param array $person Array with persons data
     *
     * @return array Returns an array with persons general details
     */
    private function handleGeneralDetails(array $person): array
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

    /**
     * This function passes a participants referrer details.
     *
     * @param array      $participant           Array with participants data
     * @param array|null $registrarPerson       Array with registrar person data
     * @param array|null $registrarOrganization Array with registrar organization data
     *
     * @return array Returns participants referrer details
     */
    private function handleReferrerDetails(array $participant, $registrarPerson = null, $registrarOrganization = null): array
    {
        if (isset($registrarOrganization)) {
            return [
                'referringOrganization'      => $registrarOrganization['name'] ?? null,
                'referringOrganizationOther' => null,
                'email'                      => $registrarPerson['emails'][0]['email'] ?? null,
            ];
        } elseif (isset($participant['referredBy'])) {
            $registrarOrganization = $this->commonGroundService->getResource($participant['referredBy']);
        }

        return [
            'referringOrganization'      => $registrarOrganization['name'] ?? null,
            'referringOrganizationOther' => $registrarOrganization['name'] ?? null,
            //todo does not check for referringOrganizationOther isn't saved separately right now
            'email' => $registrarOrganization['emails'][0]['email'] ?? null,
        ];
    }

    /**
     * This function passes a persons background details.
     *
     * @param array $person Array with persons data
     *
     * @return array Returns an array with persons background details
     */
    private function handleBackgroundDetails(array $person): array
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

    /**
     * This function passes a persons dutch NT details.
     *
     * @param array $person An array with persons data
     *
     * @return array Returns an array with persons dutch NT details
     */
    private function handleDutchNTDetails(array $person): array
    {
        return [
            'dutchNTLevel'           => $person['dutchNTLevel'] ?? null,
            'inNetherlandsSinceYear' => $person['inNetherlandsSinceYear'] ?? null,
            'languageInDailyLife'    => $person['languageInDailyLife'] ?? null,
            'knowsLatinAlphabet'     => isset($person['knowsLatinAlphabet']) ? (bool) $person['knowsLatinAlphabet'] : null,
            'lastKnownLevel'         => $person['lastKnownLevel'] ?? null,
        ];
    }

    /**
     * This function fetches educations from the given employee.
     *
     * @param array $employee           Array with employees data
     * @param false $followingEducation Bool if the employee is following a education
     *
     *@throws Exception
     *
     *@return array|null[] Returns an array of educations
     */
    public function getEducationsFromEmployee(array $employee, bool $followingEducation = false): array
    {
        $educations = [
            'lastEducation'         => [],
            'followingEducationNo'  => [],
            'followingEducationYes' => [],
            'course'                => [],
        ];

        if (isset($employee['educations'])) {
            foreach ($employee['educations'] as $education) {
                $educations = array_merge($educations, $this->setEducationType($educations, $education));
            }
        }

        if ($followingEducation) {
            $educations['followingEducation'] = $educations['followingEducationNo'] ?: $educations['followingEducationYes'];
        }

        return $educations;
    }

    /**
     * This function sets the course or a followingEducation property.
     *
     * @param array $educations Array with educations data
     * @param array $education  Array with education data
     *
     * @throws Exception
     */
    private function setEducationType(array $educations, array $education): array
    {
        switch ($education['description']) {
            case 'lastEducation':
                $educations['lastEducation'] = $education;
                break;
            case 'followingEducationNo':
                if ($educations['followingEducationYes'] == [] && $educations['followingEducationNo'] == []) {
                    $educations['followingEducationNo'] = $education;
                }
                break;
            case 'followingEducationYes':
                if ($educations['followingEducationYes'] == [] && $educations['followingEducationNo'] == []) {
                    $educations['followingEducationYes'] = $this->eavService->getObject(['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']])]);
                }
                break;
            case 'course':
                $educations['course'] = $this->eavService->getObject(['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']])]);
                break;
        }

        return $educations;
    }

    /**
     * This function passes education data to an array.
     *
     * @param array $lastEducation         An array with last education data
     * @param array $followingEducationYes An array with following education yes data
     * @param array $followingEducationNo  An array with following education no data
     *
     * @return array Returns an array with education details
     */
    private function handleEducationDetails(array $lastEducation, array $followingEducationYes, array $followingEducationNo): array
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

    /**
     * This function passes course details to an array.
     *
     * @param array $course Array with course data
     *
     * @return array Returns an array with course details
     */
    private function handleCourseDetails(array $course): array
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

    /**
     * This function passes job details to an array.
     *
     * @param array $employee Array with employee data
     *
     * @return array|null[] Returns an array with job details
     */
    private function handleJobDetails(array $employee): array
    {
        return [
            'trainedForJob'          => $employee['trainedForJob'] ?? null,
            'lastJob'                => $employee['lastJob'] ?? null,
            'dayTimeActivities'      => $employee['dayTimeActivities'] ?? null,
            'dayTimeActivitiesOther' => $employee['dayTimeActivitiesOther'] ?? null,
        ];
    }

    /**
     * This function passes motivation details to an array.
     *
     * @param array $participant Array with participant data
     *
     * @return array|null[] Returns an array with motivation details
     */
    private function handleMotivationDetails(array $participant): array
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

    /**
     * This function passes the availability details to an array.
     *
     * @param array $person Array with person data
     *
     * @return array|null[] Returns an array with availability details
     */
    private function handleAvailabilityDetails(array $person): array
    {
        return [
            'availability'      => $person['availability'] ?? null,
            'availabilityNotes' => $person['availabilityNotes'] ?? null,
        ];
    }

    /**
     * This function passes permission details to an array.
     *
     * @param array $person Array with person data
     *
     * @return array|null[] Returns an array with permission details
     */
    private function handlePermissionDetails(array $person): array
    {
        return [
            'didSignPermissionForm'                        => $person['didSignPermissionForm'] ?? null,
            'hasPermissionToShareDataWithAanbieders'       => $person['hasPermissionToShareDataWithAanbieders'] ?? null,
            'hasPermissionToShareDataWithLibraries'        => $person['hasPermissionToShareDataWithLibraries'] ?? null,
            'hasPermissionToSendInformationAboutLibraries' => $person['hasPermissionToSendInformationAboutLibraries'] ?? null,
        ];
    }
}
