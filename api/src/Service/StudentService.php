<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Availability;
use App\Entity\AvailabilityDay;
use App\Entity\Education;
use App\Entity\Email;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Registration;
use App\Entity\Student;
use App\Entity\StudentAvailability;
use App\Entity\StudentBackground;
use App\Entity\StudentCivicIntegration;
use App\Entity\StudentCourse;
use App\Entity\StudentDutchNT;
use App\Entity\StudentEducation;
use App\Entity\StudentGeneral;
use App\Entity\StudentJob;
use App\Entity\StudentMotivation;
use App\Entity\StudentPermission;
use App\Entity\StudentReferrer;
use App\Entity\Telephone;
use App\Exception\BadRequestPathException;
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
     * @return Student|object Returns student
     * @return array          Returns student
     */
    public function getStudent(string $id, bool $skipChecks = false): Student
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

        return $this->handleResult($result);
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

        // todo: make sure every subresource json array from the input follows the rules (required, datatype, etc) from the corresponding entities! (enums done)

        if (!isset($input['languageHouseId']) || $this->commonGroundService->isResource($this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $input['languageHouseId']]) == false)) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'languageHouseId');
        }
        if (!isset($input['person'])) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'person');
        }
        if (!isset($input['permissionDetails'])) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'permissionDetails');
        }
        $array = [
            'speakingLevel'     => ['BEGINNER', 'REASONABLE', 'ADVANCED'],
            'readingTestResult' => ['CAN_NOT_READ', 'A0', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2'],
            'writingTestResult' => ['CAN_NOT_WRITE', 'WRITE_NAW_DETAILS', 'WRITE_SIMPLE_TEXTS', 'WRITE_SIMPLE_LETTERS'],
            'status'            => ['REFERRED', 'ACTIVE', 'COMPLETED'],
        ];
        foreach ($array as $fieldName => $fieldValues) {
            if (isset($input[$fieldName]) && !in_array($input[$fieldName], $fieldValues)) {
                throw new BadRequestPathException('Invalid option(s) given for some fields .', $fieldName, [$fieldName => $input[$fieldName]]);
            }
        }
        if (isset($input['generalDetails'])) {
            if (isset($input['generalDetails']['civicIntegrationRequirement']) && !in_array($input['generalDetails']['familyComposition'], ['MARRIED_PARTNER', 'SINGLE', 'DIVORCED'])) {
                throw new BadRequestPathException('Invalid option(s) given for some fields .', 'generalDetails.familyComposition');
            }
        }
        if (isset($input['referrerDetails'])) {
            if (isset($input['referrerDetails']['referringOrganization']) && !in_array($input['referrerDetails']['referringOrganization'], ['UWV', 'SOCIAL_SERVICE', 'LIBRARY', 'WELFARE_WORK', 'NEIGHBORHOOD_TEAM', 'VOLUNTEER_ORGANIZATION', 'LANGUAGE_PROVIDER', 'OTHER'])) {
                throw new BadRequestPathException('Invalid option(s) given for some fields .', 'referrerDetails.referringOrganization');
            }
        }
        if (isset($input['registrar'])) {
            $this->checkPersonValues($input['registrar'], 'registrar');
        }
        if (isset($input['civicIntegrationDetails'])) {
            $this->checkStudentCivicIntegrationValues($input['civicIntegrationDetails']);
        }
        if (isset($input['person'])) {
            $this->checkPersonValues($input['person'], 'person');
        }
        if (isset($input['backgroundDetails'])) {
            $this->checkStudentBackgroundValues($input['backgroundDetails']);
        }
        if (isset($input['dutchNTDetails'])) {
            $this->checkStudentDutchNTValues($input['dutchNTDetails']);
        }
        if (isset($input['educationDetails'])) {
            if (isset($input['educationDetails']['followingEducationRightNow']) && !in_array($input['educationDetails']['followingEducationRightNow'], ['YES', 'NO', 'NO_BUT_DID_EARLIER'])) {
                throw new BadRequestPathException('Invalid option(s) given for some fields .', 'educationDetails.followingEducationRightNow');
            }
            if (isset($input['educationDetails']['education'])) {
                $this->checkStudentEducationValues($input['educationDetails']['education'], 'educationDetails.education');
            }
        }
        if (isset($input['courseDetails'])) {
            if (isset($input['courseDetails']['course'])) {
                $this->checkStudentEducationValues($input['courseDetails']['course'], 'courseDetails.course');
            }
        }
        if (isset($input['jobDetails']['dayTimeActivities'])) {
            foreach ($input['jobDetails']['dayTimeActivities'] as $activity) {
                if (!in_array($activity, ['SEARCHING_FOR_JOB', 'RE_INTEGRATION', 'SCHOOL', 'VOLUNTEER_JOB', 'JOB', 'OTHER'])) {
                    throw new BadRequestPathException('Invalid option(s) given for some fields .', 'jobDetails.dayTimeActivities');
                }
            }
        }
        if (isset($input['motivationDetails'])) {
            $this->checkStudentMotivationValues($input['motivationDetails']);
        }
    }

    /**
     * This function checks if the given student registrar array its data is valid.
     *
     * @param array $person Array with person data
     *
     * @throws Exception
     */
    public function checkPersonValues(array $person, string $pathParent)
    {
        if (isset($person['gender']) && !in_array($person['gender'], ['Male', 'Female', 'X'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', $pathParent.'.gender');
        }
        if (isset($person['contactPreference']) && !in_array($person['contactPreference'], ['PHONECALL', 'WHATSAPP', 'EMAIL', 'OTHER'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', $pathParent.'contactPreference');
        }
    }

    /**
     * This function checks if the given education array its data is valid.
     *
     * @param array $edu Array with education data
     *
     * @throws Exception
     */
    public function checkStudentEducationValues(array $edu, string $pathParent)
    {
        if (isset($edu['groupFormation']) && !in_array($edu['groupFormation'], ['INDIVIDUALLY', 'GROUP'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', $pathParent.'.groupFormation');
        }
        if (isset($edu['teacherProfessionalism']) && !in_array($edu['teacherProfessionalism'], ['PROFESSIONAL', 'VOLUNTEER', 'BOTH'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', $pathParent.'.teacherProfessionalism');
        }
        if (isset($edu['courseProfessionalism']) && !in_array($edu['courseProfessionalism'], ['PROFESSIONAL', 'VOLUNTEER', 'BOTH'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', $pathParent.'.courseProfessionalism');
        }
    }

    /**
     * This function checks if the given student civic integration array its data is valid.
     *
     * @param array $civicInt Array with students civic integration data
     *
     * @throws Exception
     */
    public function checkStudentCivicIntegrationValues(array $civicInt)
    {
        if (isset($civicInt['civicIntegrationRequirement']) && !in_array($civicInt['civicIntegrationRequirement'], ['YES', 'NO', 'CURRENTLY_WORKING_ON_INTEGRATION'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', 'civicIntegrationDetails.civicIntegrationRequirement');
        }
        if (isset($civicInt['civicIntegrationRequirementReason']) && !in_array($civicInt['civicIntegrationRequirementReason'], ['FINISHED', 'FROM_EU_COUNTRY', 'EXEMPTED_OR_ZROUTE'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', 'civicIntegrationDetails.civicIntegrationRequirementReason');
        }
    }

    /**
     * This function checks if the given student civic integration array its data is valid.
     *
     * @param array $backgroundDetails Array with students civic integration data
     *
     * @throws Exception
     */
    public function checkStudentBackgroundValues(array $backgroundDetails)
    {
        if (isset($backgroundDetails['foundVia']) && !in_array($backgroundDetails['foundVia'], ['VOLUNTEER_CENTER', 'LIBRARY_WEBSITE', 'SOCIAL_MEDIA', 'NEWSPAPER', 'VIA_VIA', 'OTHER'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', 'backgroundDetails.foundVia');
        }
        if (isset($backgroundDetails['network'])) {
            foreach ($backgroundDetails['network'] as $net) {
                if (!in_array($net, ['HOUSEHOLD_MEMBERS', 'NEIGHBORS', 'FAMILY_MEMBERS', 'FAMILY_MEMBERS', 'AID_WORKERS', 'FRIENDS_ACQUAINTANCES', 'PEOPLE_AT_MOSQUE_CHURCH', 'ACQUAINTANCES_SPEAKING_OWN_LANGUAGE', 'ACQUAINTANCES_SPEAKING_DUTCH'])) {
                    throw new BadRequestPathException('Invalid option(s) given for some fields .', 'backgroundDetails.network');
                }
            }
        }
    }

    /**
     * This function checks if the given student dutch NT array its data is valid.
     *
     * @param array $dutchNT Array with students dutch NT data
     *
     * @throws Exception
     */
    public function checkStudentDutchNTValues(array $dutchNT)
    {
        if (isset($dutchNT['dutchNTLevel']) && !in_array($dutchNT['dutchNTLevel'], ['NT1', 'NT2'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', 'dutchNTDetails.dutchNTLevel');
        }
        if (isset($dutchNT['lastKnownLevel']) && !in_array($dutchNT['lastKnownLevel'], ['A0', 'A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'UNKNOWN'])) {
            throw new BadRequestPathException('Invalid option(s) given for some fields .', 'dutchNTDetails.lastKnownLevel');
        }
    }

    /**
     * This function checks if the given student motivation array its data is valid.
     *
     * @param array $motivation Array with students motivation data
     *
     * @throws Exception
     */
    public function checkStudentMotivationValues(array $motivation)
    {
        if (isset($motivation['desiredSkills'])) {
            foreach ($motivation['desiredSkills'] as $skill) {
                if (!in_array($skill, ['KLIKTIK', 'USING_WHATSAPP', 'USING_SKYPE', 'DEVICE_FUNCTIONALITIES', 'DIGITAL_GOVERNMENT', 'RESERVE_BOOKS_IN_LIBRARY', 'ADS_ON_MARKTPLAATS', 'READ_FOR_CHILDREN', 'UNDERSTAND_PRESCRIPTIONS', 'WRITE_APPLICATION_LETTER', 'DO_ADMINISTRATION', 'CALCULATIONS_FOR_RECIPES', 'OTHER'])) {
                    throw new BadRequestPathException('Invalid option(s) given for some fields .', 'motivationDetails.desiredSkills');
                }
            }
        }
        if (isset($motivation['desiredLearningMethod'])) {
            foreach ($motivation['desiredLearningMethod'] as $method) {
                if (!in_array($method, ['IN_A_GROUP', 'ONE_ON_ONE', 'HOME_ENVIRONMENT', 'IN_LIBRARY_OR_OTHER', 'ONLINE'])) {
                    throw new BadRequestPathException('Invalid option(s) given for some fields .', 'motivationDetails.desiredLearningMethod');
                }
            }
        }
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

//        if (isset($student['participant']['dateCreated'])) {
//            $resource->setDateCreated(new \DateTime($student['participant']['dateCreated']));
//        } //todo: this is currently incorrect, timezone problem
        if (isset($student['participant']['status'])) {
            $resource->setStatus($student['participant']['status']);
        }
//        if (isset($student['registrar']['registrarMemo']['description'])) {
//            $resource->setMemo($student['registrar']['registrarMemo']['description']);
//        }
        if (isset($student['employee']['speakingLevel'])) {
            $resource->setSpeakingLevel($student['employee']['speakingLevel']);
        } else {
            $resource->setSpeakingLevel(null);
        }
        if (isset($student['participant']['readingTestResult'])) {
            $resource->setReadingTestResult($student['participant']['readingTestResult']);
        } else {
            $resource->setReadingTestResult(null);
        }
        if (isset($student['participant']['writingTestResult'])) {
            $resource->setWritingTestResult($student['participant']['writingTestResult']);
        } else {
            $resource->setWritingTestResult(null);
        }

        $resource->setLanguageHouseId($student['person']['organization']['id']);

        $this->entityManager->persist($resource);
        if (isset($student['participant']['id'])) {
            $resource->setId(Uuid::fromString($student['participant']['id']));
            $this->entityManager->persist($resource);
        }

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
        $resource->setRegistrar($this->handleRegistrar($student['participant']['registrar']));
        $resource->setCivicIntegrationDetails($this->handleCivicIntegrationDetails($student['person']));
        $resource->setPerson($this->createDTOPerson($student['person']));
        $resource->setGeneralDetails($this->handleGeneralDetails($student['person']));
        isset($student['participant']['referredBy']) ? $resource->setReferrerDetails($this->handleReferrerDetails($student['participant'])) : $resource->setReferrerDetails(null);
        $resource->setBackgroundDetails($this->handleBackgroundDetails($student['person']));
        $resource->setDutchNTDetails($this->handleDutchNTDetails($student['person']));
        if (isset($student['employee'])) {
            $student['employee'] = $this->getEducationsFromEmployee($student['employee']);
            $resource->setEducationDetails($this->handleEducationDetails($student['employee']));
            $resource->setCourseDetails($this->handleCourseDetails($student['employee']));
            $resource->setJobDetails($this->handleJobDetails($student['employee']));
        }
        $resource->setMotivationDetails($this->handleMotivationDetails($student['participant']));
        $resource->setAvailabilityDetails($this->handleAvailabilityDetails($student['person']));
        $resource->setPermissionDetails($this->handlePermissionDetails($student['person']));

        return $resource;
    }

    /**
     * This function hadnles registrar data in an Person object.
     *
     * @param null $registrar Array with registrar data
     *
     * @throws \Exception
     *
     * @return Person|null Returns Person with registrar data
     */
    private function handleRegistrar($registrar = null): ?Person
    {
        if (is_string($registrar) == true) {
            $registrar = $this->commonGroundService->getResource($registrar);
        }

        return $this->createDTOPerson($registrar);
    }

    /**
     * @param array $person
     *
     * @throws \Exception
     *
     * @return \App\Entity\Person|null
     */
    public function createDTOPerson(array $person): ?Person
    {
        $personDTO = new Person();
        if (isset($person['givenName'])) {
            $personDTO->setGivenName($person['givenName']);
        } elseif (isset($person['registrarOrganization']['name'])) {
            $personDTO->setGivenName($person['registrarOrganization']['name']);
        } else {
            return null;
        }
        isset($person['additionalName']) ? $personDTO->setAdditionalName($person['additionalName']) : $personDTO->setAdditionalName(null);
        isset($person['familyName']) ? $personDTO->setFamilyName($person['familyName']) : $personDTO->setFamilyName(null);
        isset($person['gender']) ? $personDTO->setGender($person['gender']) : $personDTO->setGender(null);
        isset($person['birthday']) ? $personDTO->setBirthday(new \DateTime($person['birthday'])) : $personDTO->setBirthday(null);
        isset($person['addresses']) ? $personDTO->setAddresses($this->createDTOAddress($person['addresses'][0])) : $personDTO->setAddresses(null);
        if (isset($person['telephones'])) {
            foreach ($person['telephones'] as $telephone) {
                $personDTO->addTelephone($this->createDTOTelephone($telephone));
            }
        }
        isset($person['emails']) ? $personDTO->setEmails($this->createDTOEmail($person['emails'][0])) : $personDTO->setEmails(null);
        isset($person['organization']) ? $personDTO->setOrganization($this->createDTOOrganization($person['organization'])) : $personDTO->createDTOOrganization(null);
        isset($person['contactPreference']) ? $personDTO->setContactPreference($person['contactPreference']) : $personDTO->setContactPreference(null);
        isset($person['contactPreferenceOther']) ? $personDTO->setContactPreferenceOther($person['contactPreferenceOther']) : $personDTO->setContactPreferenceOther(null);

        return $personDTO;
    }

    /**
     * @param array $org
     *
     * @return \App\Entity\Organization
     */
    public function createDTOOrganization(array $org): Organization
    {
        $organization = new Organization();
        isset($org['name']) ? $organization->setName($org['name']) : $organization->setName(null);
        isset($org['type']) ? $organization->setType($org['type']) : $organization->setType(null);
        isset($org['addresses'][0]) ? $organization->setAddresses($this->createDTOAddress($org['addresses'][0])) : $organization->setAddresses(null);
        isset($org['telephones'][0]) ? $organization->setTelephones($this->createDTOTelephone($org['telephones'][0])) : $organization->setTelephones(null);
        isset($org['emails'][0]) ? $organization->setEmails($this->createDTOEmail($org['emails'][0])) : $organization->setEmails(null);

        return $organization;
    }

    /**
     * @param array $address
     *
     * @return \App\Entity\Address
     */
    public function createDTOAddress(array $address): Address
    {
        $addressDTO = new Address();
        isset($address['name']) ? $addressDTO->setName($address['name']) : $addressDTO->setName(null);
        isset($address['street']) ? $addressDTO->setStreet($address['street']) : $addressDTO->setStreet(null);
        isset($address['houseNumber']) ? $addressDTO->setHouseNumber($address['houseNumber']) : $addressDTO->setHouseNumber(null);
        isset($address['houseNumberSuffix']) ? $addressDTO->setHouseNumberSuffix($address['houseNumberSuffix']) : $addressDTO->setHouseNumberSuffix(null);
        isset($address['postalCode']) ? $addressDTO->setPostalCode($address['postalCode']) : $addressDTO->setPostalCode(null);
        isset($address['locality']) ? $addressDTO->setLocality($address['locality']) : $addressDTO->setLocality(null);

        return $addressDTO;
    }

    /**
     * @param array $tel
     *
     * @return \App\Entity\Telephone
     */
    public function createDTOTelephone(array $tel): Telephone
    {
        $newTelephone = new Telephone();
        isset($tel['name']) ? $newTelephone->setName($tel['name']) : $newTelephone->setName(null);
        isset($tel['telephone']) ? $newTelephone->setTelephone($tel['telephone']) : $newTelephone->setTelephone(null);

        return $newTelephone;
    }

    /**
     * @param array $email
     *
     * @return \App\Entity\Email
     */
    public function createDTOEmail(array $email): Email
    {
        $emailDTO = new Email();
        isset($email['name']) ? $emailDTO->setName($email['name']) : $emailDTO->setName(null);
        isset($email['email']) ? $emailDTO->setEmail($email['email']) : $emailDTO->setEmail(null);

        return $emailDTO;
    }

    /**
     * This function passes a persons integration details through an array.
     *
     * @param array $person
     *
     * @throws \Exception
     *
     * @return StudentCivicIntegration|null[] Returns an array with integration details
     */
    private function handleCivicIntegrationDetails(array $person): StudentCivicIntegration
    {
        $civicIntegDetails = new StudentCivicIntegration();
        if (isset($person['civicIntegrationRequirement'])) {
            $civicIntegDetails->setCivicIntegrationRequirement($person['civicIntegrationRequirement']);
        }
        if (isset($person['civicIntegrationRequirementReason'])) {
            $civicIntegDetails->setCivicIntegrationRequirementReason($person['civicIntegrationRequirementReason']);
        }
        if (isset($person['civicIntegrationRequirementFinishDate'])) {
            $civicIntegDetails->setCivicIntegrationRequirementFinishDate(new \DateTime($person['civicIntegrationRequirementFinishDate']));
        }

        return $civicIntegDetails;
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
     * @return StudentGeneral Returns general details object
     */
    private function handleGeneralDetails(array $person): StudentGeneral
    {
        if (isset($person['ownedContactLists'][0]['people']) && $person['ownedContactLists'][0]['name'] == 'Children') {
            $childrenCount = count($person['ownedContactLists'][0]['people']);
            $childrenDatesOfBirth = '';
            $birthdayCount = 0;
            foreach ($person['ownedContactLists'][0]['people'] as $child) {
                if (isset($child['birthday'])) {
                    $birthdayCount++;

                    try {
                        $birthday = new \DateTime($child['birthday']);
                        $childrenDatesOfBirth .= $birthday->format('d-m-Y');
                    } catch (Exception $e) {
                        $childrenDatesOfBirth .= $child['birthday'];
                    }
                    if ($birthdayCount > 1) {
                        $childrenDatesOfBirth .= ', ';
                    }
                }
            }
        }

        $generalDetails = new StudentGeneral();
        isset($person['birthplace']['country']) ? $generalDetails->setCountryOfOrigin($person['birthplace']['country']) : $generalDetails->setCountryOfOrigin(null);
        isset($person['primaryLanguage']) ? $generalDetails->setNativeLanguage($person['primaryLanguage']) : $generalDetails->setNativeLanguage(null);
        $speakingLanguages = '';
        $speakingLanguagesCount = count($person['speakingLanguages']);
        if (isset($person['speakingLanguages'])) {
            foreach ($person['speakingLanguages'] as $lang) {
                $speakingLanguages .= $lang;
                if ($speakingLanguagesCount > 1) {
                    $speakingLanguages .= ', ';
                }
            }
        }
        isset($speakingLanguages) ? $generalDetails->setOtherLanguages($speakingLanguages) : $generalDetails->setOtherLanguages(null);
        isset($person['maritalStatus']) ? $generalDetails->setFamilyComposition($person['maritalStatus']) : $generalDetails->setFamilyComposition(null);
        isset($childrenCount) ? $generalDetails->setChildrenCount($childrenCount) : $generalDetails->setChildrenCount(null);
        isset($childrenDatesOfBirth) ? $generalDetails->setChildrenDatesOfBirth($childrenDatesOfBirth) : $generalDetails->setChildrenDatesOfBirth(null);

        return $generalDetails;
    }

    /**
     * This function passes a participants referrer details.
     *
     * @param array      $participant           Array with participants data
     * @param array|null $registrarPerson       Array with registrar person data
     * @param array|null $registrarOrganization Array with registrar organization data
     *
     * @return StudentReferrer Returns participants referrer details
     */
    private function handleReferrerDetails(array $participant, $registrar = null): StudentReferrer
    {
        if (isset($participant['referredBy'])) {
            $organisation = $this->commonGroundService->getResource($participant['referredBy']);
        }
        $referringOrganization = new StudentReferrer();
        isset($organisation['name']) ? $referringOrganization->setReferringOrganization($organisation['name']) : $referringOrganization->setReferringOrganization(null);
        $referringOrganization->setReferringOrganizationOther(null);
        isset($organisation['emails'][0]['email']) ? $referringOrganization->setEmail($organisation['emails'][0]['email']) : $referringOrganization->setEmail(null);

        return $referringOrganization;
//        $referringOrganization = new StudentReferrer();
//        if (isset($registrarOrganization['name'])) {
//            $referringOrganization->setReferringOrganization($registrarOrganization['name']);
//            $referringOrganization->setReferringOrganizationOther($registrarOrganization['name']);
//        } else {
//            $referringOrganization->setReferringOrganization(null);
//            $referringOrganization->setReferringOrganizationOther(null);
//        }
//        if (isset($registrarOrganization['emails'][0]['email'])) {
//            $referringOrganization->setEmail($registrarOrganization['emails'][0]['email']);
//        } else {
//            $referringOrganization->setEmail(null);
//        }

//        return new StudentReferrer();
    }

    /**
     * This function passes a persons background details.
     *
     * @param array $person Array with persons data
     *
     * @return StudentBackground Returns an array with persons background details
     */
    private function handleBackgroundDetails(array $person): StudentBackground
    {
        $studentBackground = new StudentBackground();
        $foundViaArray = ['VOLUNTEER_CENTER', 'LIBRARY_WEBSITE', 'SOCIAL_MEDIA', 'NEWSPAPER', 'VIA_VIA', 'OTHER'];
        isset($person['foundVia']) ? in_array($person['foundVia'], $foundViaArray) ? $studentBackground->setFoundVia($person['foundVia']) && $studentBackground->setFoundViaOther(null) : $studentBackground->setFoundVia(null) && $studentBackground->setFoundViaOther($person['foundVia']) : $studentBackground->setFoundVia(null) && $studentBackground->setFoundViaOther(null);
        isset($person['wentToLanguageHouseBefore']) ? $studentBackground->setWentToLanguageHouseBefore((bool) $person['wentToLanguageHouseBefore']) : $studentBackground->setWentToLanguageHouseBefore(null);
        isset($person['wentToLanguageHouseBeforeReason']) ? $studentBackground->setWentToLanguageHouseBeforeReason($person['wentToLanguageHouseBeforeReason']) : $studentBackground->setWentToLanguageHouseBeforeReason(null);
        isset($person['wentToLanguageHouseBeforeYear']) ? $studentBackground->setWentToLanguageHouseBeforeYear($person['wentToLanguageHouseBeforeYear']) : $studentBackground->setWentToLanguageHouseBeforeYear(null);
        isset($person['network']) ? $studentBackground->setNetwork($person['network']) : $studentBackground->setNetwork(null);
        isset($person['participationLadder']) ? $studentBackground->setParticipationLadder((int) $person['participationLadder']) : $studentBackground->setParticipationLadder(null);

        return $studentBackground;
    }

    /**
     * This function passes a persons dutch NT details.
     *
     * @param array $person An array with persons data
     *
     * @return StudentDutchNT Returns an array with persons dutch NT details
     */
    private function handleDutchNTDetails(array $person): StudentDutchNT
    {
        $dutchNTDetails = new StudentDutchNT();
        isset($person['dutchNTLevel']) ? $dutchNTDetails->setDutchNTLevel($person['dutchNTLevel']) : $dutchNTDetails->setDutchNTLevel(null);
        isset($person['inNetherlandsSinceYear']) ? $dutchNTDetails->setInNetherlandsSinceYear($person['inNetherlandsSinceYear']) : $dutchNTDetails->setInNetherlandsSinceYear(null);
        isset($person['languageInDailyLife']) ? $dutchNTDetails->setLanguageInDailyLife($person['languageInDailyLife']) : $dutchNTDetails->setLanguageInDailyLife(null);
        isset($person['knowsLatinAlphabet']) ? $dutchNTDetails->setKnowsLatinAlphabet($person['knowsLatinAlphabet']) : $dutchNTDetails->setKnowsLatinAlphabet(null);
        isset($person['lastKnownLevel']) ? $dutchNTDetails->setLastKnownLevel($person['lastKnownLevel']) : $dutchNTDetails->setLastKnownLevel(null);

        return $dutchNTDetails;
    }

    /**
     * This function fetches educations from the given employee.
     *
     * @param array $employee           Array with employees data
     * @param false $followingEducation Bool if the employee is following a education
     *
     * @throws Exception
     *
     * @return array|null[] Returns array of employee
     * @return array|null[] Returns an array of educations
     */
    public function getEducationsFromEmployee(array $employee, bool $followingEducation = false): array
    {
        if (isset($employee['educations'])) {
            foreach ($employee['educations'] as $edu) {
                if (isset($edu['description']) && $edu['description'] == 'course') {
                    $employee['courseDetails']['education'] = $this->eavService->getObject(['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $edu['id']])]);
                    if (isset($employee['courseDetails']['endDate'])) {
                        $employee['courseDetails']['isFollowingCourseRightNow'] = false;
                    } else {
                        $employee['courseDetails']['isFollowingCourseRightNow'] = true;
                    }
                }
                if (isset($edu['description']) && $edu['description'] == 'education') {
                    $employee['educationDetails']['education'] = $this->eavService->getObject(['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $edu['id']])]);
                    if (isset($employee['educationDetails']['endDate'])) {
                        $employee['educationDetails']['followingEducationRightNow'] = 'NO';
                    } else {
                        $employee['educationDetails']['followingEducationRightNow'] = 'YES';
                    }
                }
            }
        }

        return $employee;
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
            case 'followingEducationNo':
            case 'lastEducation':
                $educations['followingEducationRightNow'] = 'NO';
                $educations['education'] = $this->eavService->getObject(['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']])]);
                break;
            case 'followingEducationYes':
                $educations['followingEducationRightNow'] = 'YES';
                $educations['education'] = $this->eavService->getObject(['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']])]);
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
     * @param array $lastEducation An array with education data
     *
     * @return StudentEducation Returns an array with education details
     * @throws \Exception
     */
    private function handleEducationDetails(array $educationsArray): StudentEducation
    {
        $educationDetails = new StudentEducation();
        if (!isset($educationsArray['educationDetails'])) {
            $educationDetails->setEducation($this->createDTOEducation([]));
            $educationDetails->setFollowingEducationRightNow(null);

            return $educationDetails;
        }
        $educationsArray = $educationsArray['educationDetails'];
        isset($educationsArray['followingEducationRightNow']) ? $educationDetails->setFollowingEducationRightNow($educationsArray['followingEducationRightNow']) : $educationDetails->setFollowingEducationRightNow(null);
        isset($educationsArray['education']) ? $educationDetails->setEducation($this->createDTOEducation($educationsArray['education'])) : $educationDetails->setEducation(null);

        return $educationDetails;
    }

    /**
     * @param $education
     * @return \App\Entity\Education
     * @throws \Exception
     */
    public function createDTOEducation ($education): Education
    {
        $educationDTO = new Education();
        isset($education['name']) ? $educationDTO->setName($education['name']) : $educationDTO->setName(null);
        isset($education['startDate']) ? $educationDTO->setStartDate(new \DateTime($education['startDate'])) : $educationDTO->setStartDate(null);
        isset($education['endDate']) ? $educationDTO->setEnddate(new \DateTime($education['endDate'])) : $educationDTO->setEnddate(null);
        isset($education['institution']) ? $educationDTO->setInstitution($education['institution']) : $educationDTO->setInstitution(null);
        isset($education['iscedEducationLevelCode']) ? $educationDTO->setIscedEducationLevelCode($education['iscedEducationLevelCode']) : $educationDTO->setIscedEducationLevelCode(null);
        isset($education['degreeGrantedStatus']) ? $educationDTO->setDegreeGrantedStatus($education['degreeGrantedStatus']) : $educationDTO->setDegreeGrantedStatus(null);
        isset($education['groupFormation']) ? $educationDTO->setGroupFormation($education['groupFormation']) : $educationDTO->setGroupFormation(null);
        isset($education['teacherProfessionalism']) ? $educationDTO->setTeacherProfessionalism($education['teacherProfessionalism']) : $educationDTO->setTeacherProfessionalism(null);
        isset($education['courseProfessionalism']) ? $educationDTO->setCourseProfessionalism($education['courseProfessionalism']) : $educationDTO->setCourseProfessionalism(null);
        isset($education['providesCertificate']) ? $educationDTO->setProvidesCertificate((bool) $education['providesCertificate']) : $educationDTO->setProvidesCertificate(null);
        isset($education['amountOfHours']) ? $educationDTO->setAmountOfHours($education['amountOfHours']) : $educationDTO->setAmountOfHours(null);

        return $educationDTO;
    }

    /**
     * This function passes course details to an array.
     *
     * @param array $course Array with course data
     *
     * @return StudentCourse Returns course details
     * @throws \Exception
     */
    private function handleCourseDetails(array $course): StudentCourse
    {
        $courseDetails = new StudentCourse();
        if (!isset($course['courseDetails'])) {
            $courseDetails->setCourse($this->createDTOEducation([]));
            $courseDetails->setIsFollowingCourseRightNow(null);

            return $courseDetails;
        }
        $course = $course['courseDetails'];
        isset($course['isFollowingCourseRightNow']) ? $courseDetails->setIsFollowingCourseRightNow($course['isFollowingCourseRightNow']) : $courseDetails->setIsFollowingCourseRightNow(null);
        isset($course['education']) ?  $courseDetails->setCourse($this->createDTOEducation($course['education'])) : $courseDetails->setCourse(null);

        return $courseDetails;
    }

    /**
     * This function passes job details to an array.
     *
     * @param array $employee Array with employee data
     *
     * @return StudentJob|null[] Returns an array with job details
     */
    private function handleJobDetails(array $employee): StudentJob
    {
        $studentJob = new StudentJob();
        if (isset($employee['trainedForJob'])) {
            $studentJob->setTrainedForJob($employee['trainedForJob']);
        }
        if (isset($employee['lastJob'])) {
            $studentJob->setLastJob($employee['lastJob']);
        }
        if (isset($employee['dayTimeActivities'])) {
            $studentJob->setDayTimeActivities($employee['dayTimeActivities']);
        }
        if (isset($employee['dayTimeActivitiesOther'])) {
            $studentJob->setDayTimeActivitiesOther($employee['dayTimeActivitiesOther']);
        }

        return $studentJob;
    }

    /**
     * This function passes motivation details to an array.
     *
     * @param array $participant Array with participant data
     *
     * @return StudentMotivation|null[] Returns an array with motivation details
     */
    private function handleMotivationDetails(array $participant): StudentMotivation
    {
        $motivationDetails = new StudentMotivation();
        if (isset($participant['desiredSkills'])) {
            $motivationDetails->setDesiredSkills($participant['desiredSkills']);
        } else {
            $motivationDetails->setDesiredSkills(null);
        }
        $motivationDetails->setDesiredSkillsOther(null);
        if (isset($participant['hasTriedThisBefore'])) {
            $motivationDetails->setHasTriedThisBefore($participant['hasTriedThisBefore']);
        } else {
            $motivationDetails->setHasTriedThisBefore(null);
        }
        if (isset($participant['hasTriedThisBeforeExplanation'])) {
            $motivationDetails->setHasTriedThisBeforeExplanation($participant['hasTriedThisBeforeExplanation']);
        } else {
            $motivationDetails->setHasTriedThisBeforeExplanation(null);
        }
        if (isset($participant['whyWantTheseSkills'])) {
            $motivationDetails->setWhyWantTheseSkills($participant['whyWantTheseSkills']);
        } else {
            $motivationDetails->setWhyWantTheseSkills(null);
        }
        if (isset($participant['whyWantThisNow'])) {
            $motivationDetails->setWhyWantThisNow($participant['whyWantThisNow']);
        } else {
            $motivationDetails->setWhyWantThisNow(null);
        }
        if (isset($participant['desiredLearningMethod'])) {
            $motivationDetails->setDesiredLearningMethod($participant['desiredLearningMethod']);
        } else {
            $motivationDetails->setDesiredLearningMethod(null);
        }
        if (isset($participant['remarks'])) {
            $motivationDetails->setRemarks($participant['remarks']);
        } else {
            $motivationDetails->setRemarks(null);
        }

        return $motivationDetails;
    }

    /**
     * This function passes the availability details to an array.
     *
     * @param array $person Array with person data
     *
     * @return StudentAvailability|null[] Returns an array with availability details
     */
    private function handleAvailabilityDetails(array $person): StudentAvailability
    {
        $studentAvailability = new StudentAvailability();
        if (isset($person['availability'])) {
            $availability = new Availability();
            foreach ($person['availability'] as $key => $day) {
                $availabilityDay = new AvailabilityDay();
                $availabilityDay->setMorning((bool) $day['morning']);
                $availabilityDay->setAfternoon((bool) $day['afternoon']);
                $availabilityDay->setEvening((bool) $day['evening']);

                switch ($key) {
                    case 'monday':
                        $availability->setMonday($availabilityDay);
                        break;
                    case 'tuesday':
                        $availability->setTuesday($availabilityDay);
                        break;
                    case 'wednesday':
                        $availability->setWednesday($availabilityDay);
                        break;
                    case 'thursday':
                        $availability->setThursday($availabilityDay);
                        break;
                    case 'friday':
                        $availability->setFriday($availabilityDay);
                        break;
                    case 'saturday':
                        $availability->setSaturday($availabilityDay);
                        break;
                    case 'sunday':
                        $availability->setSunday($availabilityDay);
                        break;
                }
            }
            $studentAvailability->setAvailability($availability);
        } else {
            $studentAvailability->setAvailability(null);
        }
        if (isset($person['availabilityNotes'])) {
            $studentAvailability->setAvailabilityNotes($person['availabilityNotes']);
        } else {
            $studentAvailability->setAvailabilityNotes(null);
        }

        return $studentAvailability;
    }

    /**
     * This function passes permission details to an array.
     *
     * @param array $person Array with person data
     *
     * @return StudentPermission|null[] Returns an array with permission details
     */
    private function handlePermissionDetails(array $person): StudentPermission
    {
        $studentPermission = new StudentPermission();
        if (isset($person['didSignPermissionForm'])) {
            $studentPermission->setDidSignPermissionForm((bool) $person['didSignPermissionForm']);
        }
        if (isset($person['hasPermissionToShareDataWithProviders'])) {
            $studentPermission->setHasPermissionToShareDataWithProviders((bool) $person['hasPermissionToShareDataWithProviders']);
        } else {
            $studentPermission->setHasPermissionToShareDataWithProviders(false);
        }
        if (isset($person['hasPermissionToShareDataWithLibraries'])) {
            $studentPermission->setHasPermissionToShareDataWithLibraries((bool) $person['hasPermissionToShareDataWithLibraries']);
        } else {
            $studentPermission->setHasPermissionToShareDataWithLibraries(false);
        }
        if (isset($person['hasPermissionToSendInformationAboutLibraries'])) {
            $studentPermission->setHasPermissionToSendInformationAboutLibraries((bool) $person['hasPermissionToSendInformationAboutLibraries']);
        } else {
            $studentPermission->setHasPermissionToSendInformationAboutLibraries(false);
        }

        return $studentPermission;
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
        // Do some checks and error handling
        $this->checkStudentValues($input);
        $input['languageHouseUrl'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $input['languageHouseId']]);

        //todo: only get dto info here in resolver, saving objects should be moved to the studentService->saveStudent, for an example see TestResultService->saveTestResult

        // Transform DTO info to cc/person body
        $person = $this->inputToPerson($input);

        if (isset($person['organization'])) {
            // Save person->organization its subresources
            $person = $this->savePersonsOrganizationSubresources($person);
        }

        // Save cc/person
        $person = $this->ccService->saveEavPerson($person);
//        var_dump($person['@id']);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input, $person['@id']);

        // Transform registrar into cc/person and save it
        if (isset($input['registrar'])) {
            $participant['registrar'] = $this->saveRegistrarAsPerson($input['registrar']);
        }

        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant);

        $employee = $this->inputToEmployee($input, $person['@id']);
        // Save mrc/employee and create a user if email was set in the input(ToEmployee)^
        $employee = $this->mrcService->createEmployeeArray($employee, true);

        // Then save memos
        $memos = $this->saveMemos($input, $person['@id']);
        if (isset($memos['availabilityMemo']['description'])) {
            $person['availabilityNotes'] = $memos['availabilityMemo']['description'];
        }
        if (isset($memos['motivationMemo']['description'])) {
            $participant['remarks'] = $memos['motivationMemo']['description'];
        }

        // Now put together the expected result in $result['result'] for Lifely:
//        $registrar = ['registrarOrganization' => null, 'registrarPerson' => null, 'registrarMemo' => null];

        $resourceResult = $this->handleResult(['person' => $person, 'participant' => $participant, 'employee' => $employee, 'referrerDetails' => $input['referrerDetails']]);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    /**
     * This function saves registrar to cc/person.
     *
     * @param array $input Array with participant data
     *
     * @throws \Exception
     *
     * @return string Returns registrar url
     */
    private function saveRegistrarAsPerson(array $registrar): string
    {
        if (isset($registrar['addresses'])) {
            $address = $registrar['addresses'];
            unset($registrar['addresses']);
            $registrar['addresses'][0] = '/addresses/'.$this->commonGroundService->saveResource($address, ['component' => 'cc', 'type' => 'addresses'])['id'];
        }
        if (isset($registrar['telephones'])) {
            $telephones = $registrar['telephones'];
            unset($registrar['telephones']);
            foreach ($telephones as $tel) {
                $registrar['telephones'][] = '/telephones/'.$this->commonGroundService->saveResource($tel, ['component' => 'cc', 'type' => 'telephones'])['id'];
            }
        }
        if (isset($registrar['emails'])) {
            $email = $registrar['emails'];
            unset($registrar['emails']);
            $registrar['emails'][0] = '/emails/'.$this->commonGroundService->saveResource($email, ['component' => 'cc', 'type' => 'emails'])['id'];
        }
        if (isset($registrar['organization'])) {
            if (isset($registrar['organization']['addresses'])) {
                $address = $registrar['organization']['addresses'];
                unset($registrar['organization']['addresses']);
                $registrar['organization']['addresses'][0] = '/addresses/'.$this->commonGroundService->saveResource($address, ['component' => 'cc', 'type' => 'addresses'])['id'];
            }
            if (isset($registrar['organization']['telephones'])) {
                $telephones = $registrar['organization']['telephones'];
                unset($registrar['organization']['telephones']);
                $registrar['organization']['telephones'][0] = '/telephones/'.$this->commonGroundService->saveResource($telephones, ['component' => 'cc', 'type' => 'telephones'])['id'];
            }
            if (isset($registrar['organization']['emails'])) {
                $email = $registrar['organization']['emails'];
                unset($registrar['organization']['emails']);
                $registrar['organization']['emails'][0] = '/emails/'.$this->commonGroundService->saveResource($email, ['component' => 'cc', 'type' => 'emails'])['id'];
            }
        }

        return $this->commonGroundService->saveResource($registrar, ['component' => 'cc', 'type' => 'people'])['@id'];
    }

    /**
     * This function saves subresources from person->organization.
     *
     * @param array $person Array with persons data
     *
     * @throws \Exception
     *
     * @return array Returns person with given data
     */
    private function savePersonsOrganizationSubresources(array $person): array
    {
        if (isset($person['organization']['addresses'])) {
            $address = $person['organization']['addresses'];
            unset($person['organization']['addresses']);
            $person['organization']['addresses'][0] = '/addresses/'.$this->commonGroundService->saveResource($address, ['component' => 'cc', 'type' => 'addresses'])['id'];
        }
        if (isset($person['organization']['emails'])) {
            $emails = $person['organization']['emails'];
            unset($person['organization']['emails']);
            $person['organization']['emails'][0] = '/emails/'.$this->commonGroundService->saveResource($emails, ['component' => 'cc', 'type' => 'emails'])['id'];
        }
        if (isset($person['organization']['telephones'])) {
            $telephones = $person['organization']['telephones'];
            unset($person['organization']['telephones']);
            $person['organization']['telephones'][0] = '/telephones/'.$this->commonGroundService->saveResource($telephones, ['component' => 'cc', 'type' => 'telephones'])['id'];
        }
        $org = $person['organization'];
        unset($person['organization']);
        $person['organization'] = '/organizations/'.$this->commonGroundService->saveResource($org, ['component' => 'cc', 'type' => 'organizations'])['id'];

        return $person;
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
//        if (isset($input['languageHouseId'])) {
//            $person['organization'] = '/organizations/' . $input['languageHouseId'];
//        } else {
        $person = [];
//        }
        if (isset($input['civicIntegrationDetails'])) {
            $person = $this->getPersonPropertiesFromCivicIntegrationDetails($person, $input['civicIntegrationDetails']);
        }
        if (isset($input['person'])) {
            $person = $this->getPersonPropertiesFromPersonDetails($person, $input['person']);
            $person = $this->getPersonPropertiesFromContactDetails($person, $input['person'], $updatePerson);
            if (isset($input['person']['organization'])) {
                $person = $this->getPersonPropertiesFromOrganizationDetails($person, $input['person'], $updatePerson);
            }
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
        if (isset($backgroundDetails['wentToLanguageHouseBefore'])) {
            $person['wentToLanguageHouseBefore'] = (bool) $backgroundDetails['wentToLanguageHouseBefore'];
        }
        if (isset($backgroundDetails['wentToLanguageHouseBeforeReason'])) {
            $person['wentToLanguageHouseBeforeReason'] = $backgroundDetails['wentToLanguageHouseBeforeReason'];
        }
        if (isset($backgroundDetails['wentToLanguageHouseBeforeYear'])) {
            $person['wentToLanguageHouseBeforeYear'] = $backgroundDetails['wentToLanguageHouseBeforeYear'];
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
     * @param array $person       Array with persons data
     * @param array $input        Array with inputted person data
     * @param null  $updatePerson Bool if person should be updated
     *
     * @return array Returns a person array with civic integration details
     */
    private function getPersonPropertiesFromContactDetails(array $person, array $input, $updatePerson = null): array
    {
        $personName = $person['givenName'] ? $person['familyName'] ? $person['givenName'].' '.$person['familyName'] : $person['givenName'] : '';
        $person = $this->getPersonEmailsFromContactDetails($person, $input, $personName);
        $person = $this->getPersonTelephonesFromContactDetails($person, $input, $personName);
        $person = $this->getPersonAdressesFromContactDetails($person, $input, $personName);
        if (isset($updatePerson)) {
            $person = $this->updatePersonContactDetailsSubobjects($person, $updatePerson);
        }

        //todo: check in StudentService -> checkStudentValues() if other is chosen for contactPreference, if so make sure an other option is given (see learningNeedservice->checkLearningNeedValues)
        if (isset($input['contactPreference'])) {
            $person['contactPreference'] = $input['contactPreference'];
        } elseif ($input['person']['contactPreferenceOther']) {
            $person['contactPreference'] = $input['contactPreferenceOther'];
        }

        return $person;
    }

    /**
     * This function passes given organization details to the person array.
     *
     * @param array $person       Array with persons data
     * @param array $input        Array with inputted person data
     * @param null  $updatePerson Bool if person should be updated
     *
     * @return array Returns a person array with civic integration details
     */
    private function getPersonPropertiesFromOrganizationDetails(array $person, array $input, $updatePerson = null): array
    {
        if (isset($input['organization']['id'])) {
            $person['organization']['id'] = $input['organization']['id'];
        }
        if (isset($input['organization']['name'])) {
            $person['organization']['name'] = $input['organization']['name'];
        }
        if (isset($input['organization']['type'])) {
            $person['organization']['type'] = $input['organization']['type'];
        }
        if (isset($input['organization']['addresses'])) {
            $person['organization']['addresses'] = $input['organization']['addresses'];
        }
        if (isset($input['organization']['telephones'])) {
            $person['organization']['telephones'] = $input['organization']['telephones'];
        }
        if (isset($input['organization']['emails'])) {
            $person['organization']['emails'] = $input['organization']['emails'];
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
     * @param array  $person     Array with persons data
     * @param array  $input      Array with inputted persons data
     * @param string $personName Name of person as string
     *
     * @return array Returns a person array with address properties
     */
    private function getPersonAdressesFromContactDetails(array $person, array $input, $personName): array
    {
        if (isset($input['addresses']['name'])) {
            $person['addresses'][0]['name'] = $input['addresses']['name'];
        } else {
            $person['addresses'][0]['name'] = 'Address of '.$personName;
        }
        if (isset($input['addresses']['street'])) {
            $person['addresses'][0]['street'] = $input['addresses']['street'];
        }
        if (isset($input['addresses']['postalCode'])) {
            $person['addresses'][0]['postalCode'] = $input['addresses']['postalCode'];
        }
        if (isset($input['addresses']['locality'])) {
            $person['addresses'][0]['locality'] = $input['addresses']['locality'];
        }
        if (isset($input['addresses']['houseNumber'])) {
            $person['addresses'][0]['houseNumber'] = $input['addresses']['houseNumber'];
        }
        if (isset($input['addresses']['houseNumberSuffix'])) {
            $person['addresses'][0]['houseNumberSuffix'] = $input['addresses']['houseNumberSuffix'];
        }

        return $person;
    }

    /**
     * This function passes given telephones from contact details to the person array.
     *
     * @param array  $person     Array with persons data
     * @param array  $input      Array with inputted person data
     * @param string $personName Name of person as string
     *
     * @return array Returns a person array with telephone properties
     */
    private function getPersonTelephonesFromContactDetails(array $person, array $input, string $personName): array
    {
        if (isset($input['telephones'])) {
            foreach ($input['telephones'] as $key => $telephone) {
                if (isset($telephone['name'])) {
                    $person['telephones'][$key]['name'] = $telephone['name'];
                } else {
                    $person['telephones'][$key]['name'] = 'Telephone of '.$personName;
                }
                $person['telephones'][$key]['telephone'] = $telephone['telephone'];
            }
        }
//        if (isset($contactDetails['contactPersonTelephone'])) {
//            $person['telephones'][1]['name'] = 'Telephone of the contactPerson of ' . $personName;
//            $person['telephones'][1]['telephone'] = $contactDetails['contactPersonTelephone'];
//        }

        return $person;
    }

    /**
     * This function passes given emails from contact details to the person array.
     *
     * @param array  $person     Array with persons data
     * @param array  $input      Array with inputted person data
     * @param string $personName Name of person as string
     *
     * @return array Returns a person array with email properties
     */
    private function getPersonEmailsFromContactDetails(array $person, array $input, string $personName): array
    {
        if (isset($input['emails']['email'])) {
            if (isset($input['emails']['name'])) {
                $person['emails'][0]['name'] = $input['emails']['name'];
            } else {
                $person['emails'][0]['name'] = 'Email of '.$personName;
            }
            $person['emails'][0]['email'] = $input['emails']['email'];
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
        if (isset($personDetails['birthday'])) {
            $person['birthday'] = $personDetails['birthday'];
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
        $referringOrganization = [];

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
//        var_dump($personUrl); var_dump($users);die;
        if (count($users) > 0) {
            $user = $users[0];
            $employee['userId'] = $user['id'];
            $employee['email'] = $user['username'];
        }
        if (isset($input['contactDetails']['email'])) {
            // set email for creating a user in mrcService
            $employee['email'] = $input['contactDetails']['email'];
        }
//        $educations = $this->getEducationsFromEmployee($updateEmployee, true);
        if (isset($input['educationDetails'])) {
            $employee = $this->getEmployeePropertiesFromEducationDetails($employee, $input['educationDetails']);
        }
        if (isset($input['courseDetails'])) {
            $employee = $this->getEmployeePropertiesFromCourseDetails($employee, $input['courseDetails']);
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
//        if (isset($courseData['isFollowingCourseRightNow'])) {
        $newEducation = $courseData['course'];
        if (isset($courseData['id'])) {
            $newEducation['id'] = $courseData['id'];
        }
        $newEducation['description'] = 'course';
        $newEducation['isFollowingCourseRightNow'] = $courseData['isFollowingCourseRightNow'];
//            if ($courseDetails['isFollowingCourseRightNow'] == true) {
//                if (isset($courseDetails['courseName'])) {
//                    $newEducation['name'] = $courseDetails['courseName'];
//                }
//                if (isset($courseDetails['courseTeacher'])) {
//                    $newEducation['teacherProfessionalism'] = $courseDetails['courseTeacher'];
//                }
//                if (isset($courseDetails['courseGroup'])) {
//                    $newEducation['groupFormation'] = $courseDetails['courseGroup'];
//                }
//                if (isset($courseDetails['amountOfHours'])) {
//                    $newEducation['amountOfHours'] = $courseDetails['amountOfHours'];
//                }
//                $newEducation = $this->getCourseProvideCertificateFromCourseDetails($newEducation);

//            }
        $employee['educations'][] = $newEducation;
//        }

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
        $newEducation = $educationData['education'];
        if (isset($educationData['id'])) {
            $newEducation['id'] = $educationData['id'];
        }
        $newEducation['description'] = 'education';
        $newEducation['followingEducationRightNow'] = $educationData['followingEducationRightNow'];

        $employee['educations'][] = $newEducation;

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

        if (isset($educationDetails['education']['endDate'])) {
            $newEducation['endDate'] = $educationDetails['education']['endDate'];
        }
        if (isset($educationDetails['education']['iscedEducationLevelCode'])) {
            $newEducation['name'] = $educationDetails['education']['name'];
            $newEducation['iscedEducationLevelCode'] = $educationDetails['education']['iscedEducationLevelCode'];
        }
        if (isset($educationDetails['education']['degreeGrantedStatus'])) {
            if ($educationDetails['education']['degreeGrantedStatus'] == 'Granted') {
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
        if (isset($educationDetails['education']['startDate'])) {
            $newEducation['startDate'] = $educationDetails['education']['startDate'];
        }
        if (isset($educationDetails['education']['endDate'])) {
            $newEducation['endDate'] = $educationDetails['education']['endDate'];
        }
        if (isset($educationDetails['education']['iscedEducationLevelCode'])) {
            $newEducation['name'] = $educationDetails['education']['name'];
            $newEducation['iscedEducationLevelCode'] = $educationDetails['education']['iscedEducationLevelCode'];
        }
        if (isset($educationDetails['education']['institution'])) {
            $newEducation['institution'] = $educationDetails['education']['institution'];
        }
        if (isset($educationDetails['education']['providesCertificate'])) {
            if ($educationDetails['education']['providesCertificate'] == true) {
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
     * @throws \Exception
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

    /**
     * This function updates a Student with given input.
     *
     * @param array $input Array with students data
     *
     * @throws Exception
     *
     * @return object Returns a Student object
     */
    public function updateStudent(array $input, string $id = null): object
    {
        // Fetch existing data (Student)
        $student['participant'] = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id])]);
        $student['person'] = $this->eavService->getObject(['entityName' => 'people', 'componentCode' => 'cc', 'self' => $student['participant']['person']]);
        $student['employee'] = $this->getStudentEmployee($student['person']);

        // Do some checks and error handling
        $this->checkStudentValues($input);

        //todo: only get dto info here in resolver, saving objects should be moved to the studentService->saveStudent, for an example see TestResultService->saveTestResult

        // Transform DTO info to cc/person body
        $person = $this->inputToPerson($input, $student['person']);

        if (isset($person['organization'])) {
            // Save person->organization its subresources
            $person = $this->savePersonsOrganizationSubresources($person);
        }

        // Save cc/person
        $person = $this->ccService->saveEavPerson($person, $student['person']['@id']);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input, $person['@id']);

        // Transform registrar into cc/person and save it
        if (isset($input['registrar'])) {
            $participant['registrar'] = $this->saveRegistrarAsPerson($input['registrar']);
        }

        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant, $student['participant']['@id']);

        $employee = $this->inputToEmployee($input, $person['@id'], $student['employee']);
        // Save mrc/employee

        $employee = $this->mrcService->updateEmployeeArray($student['employee']['id'], $employee);

        //Then save memos
        $memos = $this->saveMemos($input, $student['person']['@id']);
        if (isset($memos['availabilityMemo']['description'])) {
            $person['availabilityNotes'] = $memos['availabilityMemo']['description'];
        }
        if (isset($memos['motivationMemo']['description'])) {
            $participant['remarks'] = $memos['motivationMemo']['description'];
        }

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->handleResult(['person' => $person, 'participant' => $participant, 'employee' => $employee, 'referrerDetails' => $input['referrerDetails']]);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    /**
     * @throws \Exception
     */
    public function deleteStudent(string $id)
    {
        $student['participant'] = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id])]);
        $student['person'] = $this->eavService->getObject(['entityName' => 'people', 'componentCode' => 'cc', 'self' => $student['participant']['person']]);
        $student['employee'] = $this->getStudentEmployee($student['person']);

        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['person' => $student['person']['@id']])['hydra:member'];
        if (isset($users)) {
            foreach ($users as $user) {
                $this->commonGroundService->deleteResource($user, $user['@id']);
            }
        }

        if (isset($student['participant']['registrar'])) {
            $this->ccService->deletePerson($this->commonGroundService->getUuidFromUrl($student['participant']['registrar']));
        }
        $this->eavService->deleteResource(null, ['component' => 'edu', 'type' => 'participants', 'id' => $student['participant']['id']]);

        if (isset($student['ownedContactLists'])) {
            $this->deleteCCOwnedContactLists($student['ownedContactLists']);
        }
        if (isset($student['birthplace'])) {
            $this->commonGroundService->deleteResource($student['birthplace'], $student['birthplace']['@id']);
        }
        if (isset($student['organization'])) {
            $this->deleteCCResource($student['organization']);
        }
        $this->deleteCCResource($student);

        if (isset($student['employee']['educations'])) {
            foreach ($student['employee']['educations'] as $edu) {
                $this->commonGroundService->deleteResource($edu, $edu['@id']);
            }
        }

        $this->eavService->deleteResource(null, ['component' => 'mrc', 'type' => 'employees', 'id' => $student['person']['id']]);
    }

    /**
     * @param array $oCLArray
     */
    public function deleteCCOwnedContactLists(array $oCLArray)
    {
        foreach ($oCLArray as $oCL) {
            $this->commonGroundService->deleteResource($oCL, $oCL['@id']);
            if (isset($oCL['people'])) {
                foreach ($oCL['people'] as $person) {
                    $this->commonGroundService->deleteResource($person, $person['@id']);
                }
            }
        }
    }

    /**
     * @param array $personOrOrg
     */
    public function deleteCCResource(array $personOrOrg)
    {
        if (isset($personOrOrg['telephones'])) {
            foreach ($personOrOrg['telephones'] as $telephone) {
                $this->commonGroundService->deleteResource($telephone, $telephone['@id']);
            }
        }
        if (isset($personOrOrg['emails'])) {
            foreach ($personOrOrg['emails'] as $email) {
                $this->commonGroundService->deleteResource($email, $email['@id']);
            }
        }
        if (isset($personOrOrg['addresses'])) {
            foreach ($personOrOrg['addresses'] as $address) {
                $this->commonGroundService->deleteResource($address, $address['@id']);
            }
            $this->commonGroundService->deleteResource($personOrOrg, $personOrOrg['@id']);
        }
    }
}
