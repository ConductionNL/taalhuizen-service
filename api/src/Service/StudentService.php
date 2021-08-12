<?php

namespace App\Service;

use App\Entity\Person;
use App\Entity\Registration;
use App\Entity\Student;
use App\Entity\StudentCivicIntegration;
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
        CommonGroundService $commonGroundService,
        CCService $ccService,
        EDUService $eduService,
        MrcService $mrcService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = new EAVService($commonGroundService);
        $this->ccService = $ccService;
        $this->eduService = $eduService;
        $this->mrcService = $mrcService;
    }

    /**
     * This function fetches the student with the given ID.
     *
     * @param string      $id         ID of the student
     * @param string|null $studentUrl URL of the student
     * @param false       $skipChecks Bool if code should skip checks or not
     *
     * @throws Exception
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
     * @throws Exception
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
            'participant' => $participant ?? null,
            'person'      => $person ?? null,
            'employee'    => $employee ?? null,
            'registrar'   => $registrar,
        ];
    }

    /**
     * This function fetches the student cc/person.
     *
     * @param array $participant Array with participants data
     * @param false $skipChecks  Bool if code should skip checks or not
     *
     * @throws Exception
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

    //TODO: maybe use AvailabilityService memo functions instead of this!: (see mrcService createEmployeeObject)

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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
        $personDetails = $input['person'];
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

    //todo Return set emails and telephones for registrar (Person)

    /**
     * This function merges a registrarOrganization and registrarPerson in an array.
     *
     * @param null $registrarPerson       Array with registrarPersons data
     * @param null $registrarOrganization Array with registrarOrganizations data
     *
     * @return Person|null[] Returns Person with registrar data
     */
    private function handleRegistrar($registrarPerson = null, $registrarOrganization = null): Person
    {
        $registrar = new Person();
        if (isset($registrarOrganization['id'])) {
            $registrar->setId($registrarOrganization['id']);
        }
        // todo how do we set organization name on a Person ?
//        if (isset($registrarOrganization['name'])) {
//            $registrar->setGivenName($registrarOrganization['name']);
//        }
        if (isset($registrarOrganization['givenName'])) {
            $registrar->setGivenName($registrarOrganization['givenName']);
        }
        if (isset($registrarOrganization['additionalName'])) {
            $registrar->setAdditionalName($registrarOrganization['additionalName']);
        }
        if (isset($registrarOrganization['familyName'])) {
            $registrar->setFamilyName($registrarOrganization['familyName']);
        }

//        if (isset($registrarOrganization['emails'])) {
//            $registrar->setE($registrarOrganization['familyName']);
//        }

        return $registrar;
    }

    /**
     * This function passes a persons integration details through an array.
     *
     * @param array $person
     *
     * @throws Exception
     *
     * @return StudentCivicIntegration Returns an array with integration details
     */
    private function handleCivicIntegrationDetails(array $person): StudentCivicIntegration
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
    private function handlePersonDetails(array $person): Person
    {
        return [
            'givenName'      => $person['givenName'] ?? null,
            'additionalName' => $person['additionalName'] ?? null,
            'familyName'     => $person['familyName'] ?? null,
            'gender'         => $person['gender'] ? $person['gender'] : 'X',
            'birthday'       => $person['birthday'] ?? null,
        ];
    }

    private function handlePerson(array $input): Person
    {
        $result = new Person();
        $result->setGivenName($input['givenName']);
        $result->setAdditionalName(isset($input['additionalName']) ? $input['additionalName'] : null);
        $result->setFamilyName($input['familyName']);
        $result->setGender(isset($input['gender']) ? $input['gender'] : null);
        $result->setBirthday(isset($input['birthday']) ? $input['birthday'] : null);
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
     * @throws Exception
     *
     * @return array|null[] Returns an array of educations
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

    /**
     * This function creates a Student object with given input.
     *
     * @param array $input Array with students data
     *
     * @throws Exception
     *
     * @return object Returns a Student object
     */
    public function createStudent(array $input): Student
    {
        if (isset($input['languageHouseId'])) {
            $languageHouseId = explode('/', $input['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
            $input['languageHouseUrl'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        } else {
            throw new Exception('languageHouseId not given');
        }

        // Do some checks and error handling
        $this->checkStudentValues($input);

        //todo: only get dto info here in resolver, saving objects should be moved to the studentService->saveStudent, for an example see TestResultService->saveTestResult

        // Transform DTO info to cc/person body
        $person = $this->inputToPerson($input);
        // Save cc/person
        $person = $this->ccService->saveEavPerson($person);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input, $person['@id']);
        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant);

        $employee = $this->inputToEmployee($input, $person['@id']);
        // Save mrc/employee and create a user if email was set in the input(ToEmployee)^
        $employee = $this->mrcService->createEmployeeArray($employee);

        // Then save memos
        $memos = $this->saveMemos($input, $person['@id']);
        if (isset($memos['availabilityMemo']['description'])) {
            $person['availabilityNotes'] = $memos['availabilityMemo']['description'];
        }
        if (isset($memos['motivationMemo']['description'])) {
            $participant['remarks'] = $memos['motivationMemo']['description'];
        }

        // Now put together the expected result in $result['result'] for Lifely:
        $registrar = ['registrarOrganization' => null, 'registrarPerson' => null, 'registrarMemo' => null];
        $resourceResult = $this->handleResult(['person' => $person, 'participant' => $participant, 'employee' => $employee, 'registrar' => $registrar]);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    /**
     * This function passes data from given input to the person array.
     *
     * @param array $input        Array with persons data
     * @param null  $updatePerson Bool if person should be updated
     *
     * @return array Returns person with given data
     */
    private function inputToPerson(array $input, $updatePerson = null): array
    {
        if (isset($input['languageHouseId'])) {
            $person['organization'] = '/organizations/'.$input['languageHouseId'];
        } else {
            $person = [];
        }
        if (isset($input['civicIntegrationDetails'])) {
            $person = $this->getPersonPropertiesFromCivicIntegrationDetails($person, $input['civicIntegrationDetails']);
        }
        if (isset($input['person'])) {
            $person = $this->getPersonPropertiesFromPersonDetails($person, $input['person']);
        }
        if (isset($input['contactDetails'])) {
            $person = $this->getPersonPropertiesFromContactDetails($person, $input['contactDetails'], $updatePerson);
        }
        if (isset($input['generalDetails'])) {
            $person = $this->getPersonPropertiesFromGeneralDetails($person, $input['generalDetails'], $updatePerson);
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
            $person = $this->getPersonPropertiesFromPermissionDetails($person, $input['permissionDetails']);
        }

        return $person;
    }

    /**
     * This function sets the persons availability  with the given availability details.
     *
     * @param array $person            Array with persons data
     * @param array $permissionDetails Array with permission details
     *
     * @return array Returns person array
     */
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

    /**
     * This function sets the persons availability  with the given availability details.
     *
     * @param array $person              Array with persons data
     * @param array $availabilityDetails Array with availability details
     *
     * @return array Returns person array
     */
    private function getPersonPropertiesFromAvailabilityDetails(array $person, array $availabilityDetails)
    {
        if (isset($availabilityDetails['availability'])) {
            $person['availability'] = $availabilityDetails['availability'];
        }

        return $person;
    }

    /**
     * This function sets the persons DutchN NT details  with the given Dutch NT details.
     *
     * @param array $person         Array with persons data
     * @param array $dutchNTDetails Array with Dutch NT Details
     *
     * @return array Returns person array
     */
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

    /**
     * This function sets the person background details with the given background details.
     *
     * @param array $person            Array with persons data
     * @param array $backgroundDetails Array with background details
     *
     * @return array Returns a person array with background details
     */
    private function getPersonPropertiesFromBackgroundDetails(array $person, array $backgroundDetails): array
    {
        //todo: check in StudentService -> checkStudentValues() for enum options and if other is chosen make sure an other option is given (see learningNeedservice->checkLearningNeedValues)
        // (VOLUNTEER_CENTER, LIBRARY_WEBSITE, SOCIAL_MEDIA, NEWSPAPER, VIA_VIA, OTHER)
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
        //todo: check in StudentService -> checkStudentValues() if all values in this array are one of the enum values
        // (HOUSEHOLD_MEMBERS, NEIGHBORS, FAMILY_MEMBERS, AID_WORKERS, FRIENDS_ACQUAINTANCES, PEOPLE_AT_MOSQUE_CHURCH, ACQUAINTANCES_SPEAKING_OWN_LANGUAGE, ACQUAINTANCES_SPEAKING_DUTCH)
        if (isset($backgroundDetails['network'])) {
            $person['network'] = $backgroundDetails['network'];
        }
        if (isset($backgroundDetails['participationLadder'])) {
            $person['participationLadder'] = (int) $backgroundDetails['participationLadder'];
        }

        return $person;
    }

    /**
     * This function updates the person with the given general details.
     *
     * @param array $person         Array with persons data
     * @param array $generalDetails Array with general details data
     * @param null  $updatePerson   Bool if person should be updated or not
     *
     * @return array Returns a person with properties from general details
     */
    private function getPersonPropertiesFromGeneralDetails(array $person, array $generalDetails, $updatePerson = null): array
    {
        if (isset($generalDetails['countryOfOrigin'])) {
            $person = $this->setPersonBirthplaceFromCountryOfOrigin($person, $generalDetails['countryOfOrigin'], $updatePerson);
        }
        //todo check in StudentService -> checkStudentValues() if this is a iso country code (NL)
        if (isset($generalDetails['nativeLanguage'])) {
            $person['primaryLanguage'] = $generalDetails['nativeLanguage'];
        }
        if (isset($generalDetails['otherLanguages'])) {
            $person['speakingLanguages'] = explode(',', $generalDetails['otherLanguages']);
        }
        //todo: check in StudentService -> checkStudentValues() if this is one of the enum values ("MARRIED_PARTNER","SINGLE","DIVORCED","WIDOW")
        if (isset($generalDetails['familyComposition'])) {
            $person['maritalStatus'] = $generalDetails['familyComposition'];
        }

        // Create the children of this person
        return $this->setPersonChildrenFromGeneralDetails($person, $generalDetails, $updatePerson);
    }

    /**
     * This function sets the persons children properties from the given general details.
     *
     * @param array $person
     * @param array $generalDetails
     * @param null  $updatePerson   Bool if person should be updated
     *
     * @return array Returns person array with children properties
     */
    private function setPersonChildrenFromGeneralDetails(array $person, array $generalDetails, $updatePerson = null): array
    {
        if (isset($generalDetails['childrenCount'])) {
            $childrenCount = (int) $generalDetails['childrenCount'];
        }
        if (isset($generalDetails['childrenDatesOfBirth'])) {
            $childrenDatesOfBirth = $this->setChildrenDatesOfBirthFromGeneralDetails($generalDetails['childrenDatesOfBirth']);
            if (!isset($childrenCount)) {
                $childrenCount = count($childrenDatesOfBirth);
            }
        }
        if (isset($childrenCount)) {
            $person['ownedContactLists'][0] = $this->setChildrenFromChildrenCount($person, $childrenCount, $childrenDatesOfBirth ?? null);
            if (isset($updatePerson['ownedContactLists'][0]['id'])) {
                $person = $this->updatePersonChildrenContactList($person, $updatePerson);
            }
        }

        return $person;
    }

    /**
     * This function updates the persons children contact list with given person.
     *
     * @param array $person       Array with persons data
     * @param array $updatePerson Array with updated persons data
     *
     * @return array Returns an updated person array
     */
    private function updatePersonChildrenContactList(array $person, array $updatePerson): array
    {
        // todo: when doing an update, make sure to not keep creating new objects, this is now solved by just deleting all children objects and creating new ones^:
        if (isset($updatePerson['ownedContactLists'][0]['people'])) {
            foreach ($updatePerson['ownedContactLists'][0]['people'] as $key => $child) {
                $this->commonGroundService->deleteResource($child, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $child['id']]));
                unset($updatePerson['ownedContactLists'][0]['people'][$key]);
            }
        }
        //merge person birthplace into updatePerson birthplace and update the updatePerson birthplace
        $contactList = array_merge($updatePerson['ownedContactLists'][0], $person['ownedContactLists'][0]);
        $this->commonGroundService->updateResource($contactList, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'contact_lists', 'id' => $updatePerson['ownedContactLists'][0]['id']]));

        //unset person ownedContactLists
        unset($person['ownedContactLists']);

        return $person;
    }

    /**
     * This function sets children from children count.
     *
     * @param array $person               Array with persons data
     * @param int   $childrenCount        Int that counts children
     * @param array $childrenDatesOfBirth Array with childrens date of births
     *
     * @return array Returns an array with childrens data
     */
    private function setChildrenFromChildrenCount(array $person, int $childrenCount, array $childrenDatesOfBirth): array
    {
        $children = [];
        for ($i = 0; $i < $childrenCount; $i++) {
            $child = [
                'givenName' => 'Child '.($i + 1).' of '.$person['givenName'] ?? '',
            ];
            if (isset($childrenDatesOfBirth[$i])) {
                $child['birthday'] = $childrenDatesOfBirth[$i];
            }
            $children[] = $child;
        }

        return [
            'name'        => 'Children',
            'description' => 'The children of '.$person['givenName'] ?? 'this owner',
            'people'      => $children,
        ];
    }

    /**
     * This function sets the children dates of birth from given general details.
     *
     * @param string $childrenDatesOfBirth String that holds dates of birth of children.
     *
     * @return array
     */
    private function setChildrenDatesOfBirthFromGeneralDetails(string $childrenDatesOfBirth): array
    {
        $childrenDatesOfBirth = explode(',', $childrenDatesOfBirth);
        foreach ($childrenDatesOfBirth as $key => $childrenDateOfBirth) {
            try {
                new \DateTime($childrenDateOfBirth);
            } catch (Exception $e) {
                unset($childrenDatesOfBirth[$key]);
            }
        }

        return $childrenDatesOfBirth;
    }

    /**
     * This function sets the persons birthplace from the given country of origins.
     *
     * @param array  $person          Array with persons data
     * @param string $countryOfOrigin String that holds country of origin
     * @param null   $updatePerson    Bool if person should be updated
     *
     * @return array Returns person array with birthplace property
     */
    private function setPersonBirthplaceFromCountryOfOrigin(array $person, string $countryOfOrigin, $updatePerson = null): array
    {
        $person['birthplace'] = [
            'country' => $countryOfOrigin,
        ];
        if (isset($updatePerson['birthplace']['id'])) {
            //merge person birthplace into updatePerson birthplace and update the updatePerson birthplace
            $address = array_merge($updatePerson['birthplace'], $person['birthplace']);
            $this->commonGroundService->updateResource($address, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'addresses', 'id' => $updatePerson['birthplace']['id']]));

            //unset person birthplace
            unset($person['birthplace']);
        }

        return $person;
    }

    /**
     * This function passes given contact details to the person array.
     *
     * @param array $person         Array with persons data
     * @param array $contactDetails Array with contact details data
     * @param null  $updatePerson   Bool if person should be updated
     *
     * @return array Returns a person array with civic integration details
     */
    private function getPersonPropertiesFromContactDetails(array $person, array $contactDetails, $updatePerson = null): array
    {
        $personName = $person['givenName'] ? $person['familyName'] ? $person['givenName'].' '.$person['familyName'] : $person['givenName'] : '';
        $person = $this->getPersonEmailsFromContactDetails($person, $contactDetails, $personName);
        $person = $this->getPersonTelephonesFromContactDetails($person, $contactDetails, $personName);
        $person = $this->getPersonAdressesFromContactDetails($person, $contactDetails, $personName);
        if (isset($updatePerson)) {
            $person = $this->updatePersonContactDetailsSubobjects($person, $updatePerson);
        }

        //todo: check in StudentService -> checkStudentValues() if other is chosen for contactPreference, if so make sure an other option is given (see learningNeedservice->checkLearningNeedValues)
        if (isset($contactDetails['contactPreference'])) {
            $person['contactPreference'] = $contactDetails['contactPreference'];
        } elseif ($contactDetails['contactPreferenceOther']) {
            $person['contactPreference'] = $contactDetails['contactPreferenceOther'];
        }

        return $person;
    }

    /**
     * This function updates the person with the contact details's subobjects.
     *
     * @param array $person       Array with persons data
     * @param array $updatePerson Array with data the person needs to be updated with
     *
     * @return array Returns a person array with updated contact details
     */
    private function updatePersonContactDetailsSubobjects(array $person, array $updatePerson): array
    {
        if (isset($person['emails'][0]) && isset($updatePerson['emails'][0]['id'])) {
            //merge person emails into updatePerson emails and update the updatePerson email
            $email = array_merge($updatePerson['emails'][0], $person['emails'][0]);
            $this->commonGroundService->updateResource($email, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'emails', 'id' => $updatePerson['emails'][0]['id']]));

            //unset person emails
            unset($person['emails']);
        }
        if (isset($person['telephones']) && isset($updatePerson['telephones'])) {
            $person = $this->updatePersonContactDetailsTelephones($person, $updatePerson);
        }
        if (isset($person['addresses'][0]) && isset($updatePerson['addresses'][0]['id'])) {
            //merge person addresses into updatePerson addresses and update the updatePerson address
            $address = array_merge($updatePerson['addresses'][0], $person['addresses'][0]);
            $this->commonGroundService->updateResource($address, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'addresses', 'id' => $updatePerson['addresses'][0]['id']]));

            //unset person addresses
            unset($person['addresses']);
        }

        return $person;
    }

    /**
     * This function updates the person with the contact details's telephones.
     *
     * @param array $person       Array with persons data
     * @param array $updatePerson Array with data the person needs to be updated with
     *
     * @return array Returns a person array with updated contact details telephones
     */
    private function updatePersonContactDetailsTelephones(array $person, array $updatePerson): array
    {
        if (isset($person['telephones'][0]) && isset($updatePerson['telephones'][0]['id'])) {
            //merge person telephones into updatePerson telephones and update the updatePerson telephone
            $telephone = array_merge($updatePerson['telephones'][0], $person['telephones'][0]);
            $this->commonGroundService->updateResource($telephone, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'telephones', 'id' => $updatePerson['telephones'][0]['id']]));
        }
        if (isset($person['telephones'][1]) && isset($updatePerson['telephones'][1]['id'])) {
            //merge person telephones into updatePerson telephones and update the updatePerson telephone
            $telephone = array_merge($updatePerson['telephones'][1], $person['telephones'][1]);
            $this->commonGroundService->updateResource($telephone, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'telephones', 'id' => $updatePerson['telephones'][1]['id']]));
        }
        //unset person emails
        unset($person['telephones']);

        return $person;
    }

    /**
     * This function passes given addresses from contact details to the person array.
     *
     * @param array  $person         Array with persons data
     * @param array  $contactDetails Array with contact details data
     * @param string $personName     Name of person as string
     *
     * @return array Returns a person array with address properties
     */
    private function getPersonAdressesFromContactDetails(array $person, array $contactDetails, $personName): array
    {
        $person['addresses'][0]['name'] = 'Address of '.$personName;
        if (isset($contactDetails['street'])) {
            $person['addresses'][0]['street'] = $contactDetails['street'];
        }
        if (isset($contactDetails['postalCode'])) {
            $person['addresses'][0]['postalCode'] = $contactDetails['postalCode'];
        }
        if (isset($contactDetails['locality'])) {
            $person['addresses'][0]['locality'] = $contactDetails['locality'];
        }
        if (isset($contactDetails['houseNumber'])) {
            $person['addresses'][0]['houseNumber'] = $contactDetails['houseNumber'];
        }
        if (isset($contactDetails['houseNumberSuffix'])) {
            $person['addresses'][0]['houseNumberSuffix'] = $contactDetails['houseNumberSuffix'];
        }

        return $person;
    }

    /**
     * This function passes given telephones from contact details to the person array.
     *
     * @param array  $person         Array with persons data
     * @param array  $contactDetails Array with contact details data
     * @param string $personName     Name of person as string
     *
     * @return array Returns a person array with telephone properties
     */
    private function getPersonTelephonesFromContactDetails(array $person, array $contactDetails, string $personName): array
    {
        if (isset($contactDetails['telephone'])) {
            $person['telephones'][0]['name'] = 'Telephone of '.$personName;
            $person['telephones'][0]['telephone'] = $contactDetails['telephone'];
        }
        if (isset($contactDetails['contactPersonTelephone'])) {
            $person['telephones'][1]['name'] = 'Telephone of the contactPerson of '.$personName;
            $person['telephones'][1]['telephone'] = $contactDetails['contactPersonTelephone'];
        }

        return $person;
    }

    /**
     * This function passes given emails from contact details to the person array.
     *
     * @param array  $person         Array with persons data
     * @param array  $contactDetails Array with contact details data
     * @param string $personName     Name of person as string
     *
     * @return array Returns a person array with email properties
     */
    private function getPersonEmailsFromContactDetails(array $person, array $contactDetails, string $personName): array
    {
        if (isset($contactDetails['email'])) {
            $person['emails'][0]['name'] = 'Email of '.$personName;
            $person['emails'][0]['email'] = $contactDetails['email'];
        }

        return $person;
    }

    /**
     * This function passes given person details to the person array.
     *
     * @param array $person        Array with persons data
     * @param array $personDetails Array with person details data
     *
     * @return array Returns a person array with civic integration details
     */
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

    /**
     * This function passes given civic integration details to the person array.
     *
     * @param array $person                  Array with persons data
     * @param array $civicIntegrationDetails Array with civic integration details data
     *
     * @return array Returns a person array with civic integration details
     */
    private function getPersonPropertiesFromCivicIntegrationDetails(array $person, array $civicIntegrationDetails): array
    {
        if (isset($civicIntegrationDetails['civicIntegrationRequirement'])) {
            $person['civicIntegrationRequirement'] = $civicIntegrationDetails['civicIntegrationRequirement'];
        }
        if (isset($civicIntegrationDetails['civicIntegrationRequirementReason'])) {
            $person['civicIntegrationRequirementReason'] = $civicIntegrationDetails['civicIntegrationRequirementReason'];
        }
        if (isset($civicIntegrationDetails['civicIntegrationRequirementFinishDate'])) {
            $person['civicIntegrationRequirementFinishDate'] = $civicIntegrationDetails['civicIntegrationRequirementFinishDate'];
        }

        return $person;
    }

    /**
     * This function passes the given input to an participant.
     *
     * @param array       $input       Array of given data
     * @param string|null $ccPersonUrl String that holds the person URL
     *
     * @throws Exception
     *
     * @return array Returns an participant array
     */
    private function inputToParticipant(array $input, string $ccPersonUrl = null): array
    {
        // Add cc/person to this edu/participant
        if (isset($ccPersonUrl)) {
            $participant['person'] = $ccPersonUrl;
        } else {
            $participant = [];
        }

        $participant['status'] = 'accepted';

        // EAV or Result objects?
        if (isset($input['readingTestResult'])) {
            $participant['readingTestResult'] = $input['readingTestResult'];
        }
        if (isset($input['writingTestResult'])) {
            $participant['writingTestResult'] = $input['writingTestResult'];
        }

        if (isset($input['languageHouseUrl'])) {
            $programs = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'programs'], ['provider' => $input['languageHouseUrl']])['hydra:member'];
            if (count($programs) > 0) {
                $participant['program'] = '/programs/'.$programs[0]['id'];
            } else {
                throw new Exception('Invalid request, '.$input['languageHouseUrl'].' does not have an existing program (edu/program)!');
            }
        }

        if (isset($input['referrerDetails'])) {
            $participant = $this->getParticipantPropertiesFromReferrerDetails($participant, $input['referrerDetails']);
        }
        if (isset($input['motivationDetails'])) {
            $participant = $this->getParticipantPropertiesFromMotivationDetails($participant, $input['motivationDetails']);
        }

        return $participant;
    }

    /**
     * This function sets the participant motivation details.
     *
     * @param array $participant       Array with participant details
     * @param array $motivationDetails Array with motivation details
     *
     * @return array Returns participant array
     */
    private function getParticipantPropertiesFromMotivationDetails(array $participant, array $motivationDetails): array
    {
        if (isset($motivationDetails['desiredSkills'])) {
            $participant['desiredSkills'] = $motivationDetails['desiredSkills'];
        }
        if (isset($motivationDetails['desiredSkillsOther'])) {
            $participant['desiredSkillsOther'] = $motivationDetails['desiredSkillsOther'];
        }
        if (isset($motivationDetails['hasTriedThisBefore'])) {
            $participant['hasTriedThisBefore'] = $motivationDetails['hasTriedThisBefore'];
        }
        if (isset($motivationDetails['hasTriedThisBeforeExplanation'])) {
            $participant['hasTriedThisBeforeExplanation'] = $motivationDetails['hasTriedThisBeforeExplanation'];
        }
        if (isset($motivationDetails['whyWantTheseSkills'])) {
            $participant['whyWantTheseSkills'] = $motivationDetails['whyWantTheseSkills'];
        }
        if (isset($motivationDetails['whyWantThisNow'])) {
            $participant['whyWantThisNow'] = $motivationDetails['whyWantThisNow'];
        }
        if (isset($motivationDetails['desiredLearningMethod'])) {
            $participant['desiredLearningMethod'] = $motivationDetails['desiredLearningMethod'];
        }

        return $participant;
    }

    /**
     * This function sets the participation referrer details.
     *
     * @param array $participant     Array with participant data
     * @param array $referrerDetails Array with referrer details data
     *
     * @return array Returns participant array
     */
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
        $referringOrganization = $this->commonGroundService->saveResource($referringOrganization, ['component' => 'cc', 'type' => 'organizations']);

        if (isset($referrerDetails['email'])) {
            if (isset($referringOrganization['emails'][0])) {
                $referringOrganization['emails'][0]['email'] = $referrerDetails['email'];
                $email = $this->commonGroundService->saveResource($referringOrganization['emails'][0], $referringOrganization['emails'][0]['@id']);
            } else {
                $email['name'] = 'Email '.$referringOrganization['name'];
                $email['email'] = $referrerDetails['email'];
                $email['organization'] = '/organizations/'.$referringOrganization['id'];
                $email = $this->commonGroundService->saveResource($email, ['component' => 'cc', 'type' => 'emails']);
            }
            $referringOrganization['emails'][0] = '/emails/'.$email['id'];
            $referringOrganization = $this->commonGroundService->saveResource($referringOrganization, ['component' => 'cc', 'type' => 'organizations']);
        }

        $participant['referredBy'] = $referringOrganization['@id'];

        return $participant;
    }

    /**
     * This function passes input to an employee array.
     *
     * @param array  $input          Array with employee data
     * @param string $personUrl      String that holds persons URL
     * @param null   $updateEmployee Bool if employee needs to be updated if not
     *
     * @throws Exception
     *
     * @return array Returns employee array
     */
    private function inputToEmployee(array $input, $personUrl, $updateEmployee = []): array
    {
        $employee = ['person' => $personUrl];
        //check if this person has a user and if so add its id to the employee body as userId
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['person' => $personUrl])['hydra:member'];
        if (count($users) > 0) {
            $user = $users[0];
            $employee['userId'] = $user['id'];
            $employee['email'] = $user['username'];
        }
        if (isset($input['contactDetails']['email'])) {
            // set email for creating a user in mrcService
            $employee['email'] = $input['contactDetails']['email'];
        }
        $educations = $this->getEducationsFromEmployee($updateEmployee, true);
        if (isset($input['educationDetails'])) {
            $employee = $this->getEmployeePropertiesFromEducationDetails($employee, ['lastEducation' => $educations['lastEducation'], 'followingEducation' => $educations['followingEducation'], 'details' => $input['educationDetails']]);
        }
        if (isset($input['courseDetails'])) {
            $employee = $this->getEmployeePropertiesFromCourseDetails($employee, ['course' => $educations['course'], 'details' => $input['courseDetails']]);
        }
        if (isset($input['jobDetails'])) {
            $employee = $this->getEmployeePropertiesFromJobDetails($employee, $input['jobDetails']);
        }
        if (isset($input['speakingLevel'])) {
            $employee['speakingLevel'] = $input['speakingLevel'];
        }

        return $employee;
    }

    /**
     * This function retrieves the employee properties from job details.
     *
     * @param array $employee   Array with employee data
     * @param array $jobDetails Array with job details
     *
     * @return array Returns employee array
     */
    private function getEmployeePropertiesFromJobDetails(array $employee, array $jobDetails): array
    {
        //todo make sure these attributes exist in eav! fixtures and online!
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

    /**
     * This function retrieves employee properties from course details.
     *
     * @param array $employee   Array with employee data
     * @param array $courseData
     *
     * @return array Returns employee array
     */
    private function getEmployeePropertiesFromCourseDetails(array $employee, array $courseData): array
    {
        $courseDetails = $courseData['details'];
        $course = $courseData['course'];
        if (isset($courseDetails['isFollowingCourseRightNow'])) {
            if (isset($course['id'])) {
                $newEducation['id'] = $course['id'];
            }
            $newEducation['description'] = 'course';
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
                $newEducation = $this->getCourseProvideCertificateFromCourseDetails($newEducation);
            }
            $employee['educations'][] = $newEducation;
        }

        return $employee;
    }

    /**
     * This function retrieves course provider certificate from course details.
     *
     * @param array $newEducation Array with new education data
     *
     * @return array Returns new education array
     */
    private function getCourseProvideCertificateFromCourseDetails(array $newEducation): array
    {
        if (isset($courseDetails['doesCourseProvideCertificate'])) {
            if ($courseDetails['doesCourseProvideCertificate'] == true) {
                $newEducation['providesCertificate'] = true;
            } else {
                $newEducation['providesCertificate'] = false;
            }
        }

        return $newEducation;
    }

    /**
     * This function set employee properties from given education details.
     *
     * @param array $employee      Array with employee data
     * @param array $educationData
     *
     * @return array Returns employee array
     */
    private function getEmployeePropertiesFromEducationDetails(array $employee, array $educationData): array
    {
        $educationDetails = $educationData['details'];
        $lastEducation = $educationData['lastEducation'];
        $followingEducation = $educationData['followingEducation'];
        if (isset($educationDetails['lastFollowedEducation'])) {
            $employee['educations'][] = $this->getLastEducationFromEducationDetails($educationDetails, $lastEducation);
        }

        if (isset($educationDetails['followingEducationRightNow'])) {
            $newEducation = [];
            if (isset($followingEducation['id'])) {
                $newEducation['id'] = $followingEducation['id'];
            }
            if ($educationDetails['followingEducationRightNow'] == 'YES') {
                $newEducation = $this->getFollowingEducationYesFromEducationDetails($educationDetails, $newEducation);
            } else {
                $newEducation = $this->getFollowingEducationNoFromEducationDetails($educationDetails, $newEducation);
            }
            $employee['educations'][] = $newEducation;
        }

        return $employee;
    }

    /**
     * This function retrieves following education from education details.
     *
     * @param array $educationDetails Array with education details
     * @param $newEducation array new education
     *
     * @return array Returns new education array
     */
    private function getFollowingEducationNoFromEducationDetails(array $educationDetails, array $newEducation): array
    {
        $newEducation['description'] = 'followingEducationNo';
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

        return $newEducation;
    }

    /**
     * This function retrieves the following education from education details.
     *
     * @param array $educationDetails Array with education details
     * @param array $newEducation     Array with new education
     *
     * @return array Returns a new education array
     */
    private function getFollowingEducationYesFromEducationDetails(array $educationDetails, array $newEducation): array
    {
        $newEducation['description'] = 'followingEducationYes';
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

        return $newEducation;
    }

    /**
     * This function retrieves the last eduction from education details.
     *
     * @param array $educationDetails Array with education details
     * @param null  $lastEducation    Bool if this is the last education
     *
     * @return array Returns new education array
     */
    private function getLastEducationFromEducationDetails(array $educationDetails, $lastEducation = null): array
    {
        $newEducation = [
            'name'                    => $educationDetails['lastFollowedEducation'],
            'description'             => 'lastEducation',
            'iscedEducationLevelCode' => $educationDetails['lastFollowedEducation'],
        ];
        if (isset($lastEducation['id'])) {
            $newEducation['id'] = $lastEducation['id'];
        }
        if (isset($educationDetails['didGraduate'])) {
            if ($educationDetails['didGraduate'] == true) {
                $newEducation['degreeGrantedStatus'] = 'Granted';
            } else {
                $newEducation['degreeGrantedStatus'] = 'notGranted';
            }
        }

        return $newEducation;
    }

    /**
     * This function saves memos with given input.
     *
     * @param array  $input       Array with students data
     * @param string $ccPersonUrl Persons URL as string
     *
     * @return array[] Returns array with memo properties
     */
    private function saveMemos(array $input, string $ccPersonUrl): array
    {
        $availabilityMemo = [];
        $motivationMemo = [];
        $input['languageHouseUrl'] ?? $input['languageHouseUrl'] = null;

        //TODO: maybe use AvailabilityService memo functions instead of this!: (see mrcService createEmployeeArray)
        if (isset($input['availabilityDetails'])) {
            if (isset($input['id'])) {
                //todo: also use author as filter, for this: get participant->program->provider (= languageHouseUrl when this memo was created)
                $availabilityMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['name' => 'Availability notes', 'topic' => $ccPersonUrl])['hydra:member'];
                if (count($availabilityMemos) > 0) {
                    $availabilityMemo = $availabilityMemos[0];
                }
            }
            $availabilityMemo = array_merge($availabilityMemo, $this->getMemoFromAvailabilityDetails($input['availabilityDetails'], $ccPersonUrl, $input['languageHouseUrl']));
            if (!isset($availabilityMemo['author'])) {
                $availabilityMemo['author'] = $ccPersonUrl;
            }
            $availabilityMemo = $this->commonGroundService->saveResource($availabilityMemo, ['component' => 'memo', 'type' => 'memos']);
        }

        if (isset($input['motivationDetails'])) {
            if (isset($input['id'])) {
                //todo: also use author as filter, for this: get participant->program->provider (= languageHouseUrl when this memo was created)
                $motivationMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['name' => 'Remarks', 'topic' => $ccPersonUrl])['hydra:member'];
                if (count($motivationMemos) > 0) {
                    $motivationMemo = $motivationMemos[0];
                }
            }
            $motivationMemo = array_merge($motivationMemo, $this->getMemoFromMotivationDetails($input['motivationDetails'], $ccPersonUrl, $input['languageHouseUrl']));
            if (!isset($motivationMemo['author'])) {
                $motivationMemo['author'] = $ccPersonUrl;
            }
            $motivationMemo = $this->commonGroundService->saveResource($motivationMemo, ['component' => 'memo', 'type' => 'memos']);
        }

        return [
            'availabilityMemo' => $availabilityMemo,
            'motivationMemo'   => $motivationMemo,
        ];
    }

    /**
     * This function retrieves memos from the given motivation details.
     *
     * @param array       $motivationDetails Array with motivation details data
     * @param string      $ccPersonUrl       Persons URL as string
     * @param string|null $languageHouseUrl  Language house URL as string
     *
     * @return array Returns a memo as array
     */
    private function getMemoFromMotivationDetails(array $motivationDetails, string $ccPersonUrl, string $languageHouseUrl = null): array
    {
        $memo['name'] = 'Remarks';
        $memo['description'] = $motivationDetails['remarks'];
        $memo['topic'] = $ccPersonUrl;
        if (isset($languageHouseUrl)) {
            $memo['author'] = $languageHouseUrl;
        }

        return $memo;
    }

    /**
     * This function retrieves memos from the given availability details.
     *
     * @param array       $availabilityDetails Array with availability details data
     * @param string      $ccPersonUrl         Persons URL as string
     * @param string|null $languageHouseUrl    Language house URL as string
     *
     * @return array Returns a memo as array
     */
    private function getMemoFromAvailabilityDetails(array $availabilityDetails, string $ccPersonUrl, string $languageHouseUrl = null): array
    {
        $memo['name'] = 'Availability notes';
        $memo['description'] = $availabilityDetails['availabilityNotes'];
        $memo['topic'] = $ccPersonUrl;
        if (isset($languageHouseUrl)) {
            $memo['author'] = $languageHouseUrl;
        }

        return $memo;
    }
}
