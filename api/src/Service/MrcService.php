<?php

namespace App\Service;

use App\Entity\Education;
use App\Entity\Employee;
use App\Entity\Person;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class MrcService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;
    private UcService $ucService;
    private EAVService $eavService;
    private BsService $bsService;
    private AvailabilityService $availabilityService;

    /**
     * MrcService constructor.
     *
     * @param LayerService $layerService
     * @param UcService    $ucService
     */
    public function __construct(
        LayerService $layerService,
        UcService $ucService
    ) {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->ucService = $ucService;
        $this->bsService = $layerService->bsService;
        $this->ccService = new CCService($layerService);
        $this->eavService = new EAVService($layerService->commonGroundService);
        $this->availabilityService = new AvailabilityService($layerService);
    }

    /**
     * Gets employees for an organization.
     *
     * @param string|null $languageHouseId The id of the language house to get the employees for
     * @param string|null $providerId      The id of the provider to get the employees for
     *
     * @return ArrayCollection A collection of employees for the organization provided (or BISC if none is provided)
     */
    public function getEmployees(?string $languageHouseId = null, ?string $providerId = null): ArrayCollection
    {
        $employees = new ArrayCollection();
        if ($languageHouseId) {
            $results = $this->eavService->getObjectList('employees', 'mrc', ['organization' => $this->commonGroundService->cleanUrl(['id' => $languageHouseId, 'component' => 'cc', 'type' => 'organizations'])])['hydra:member'];
        } elseif (!$providerId) {
            $results = $this->eavService->getObjectList('employees', 'mrc', ['provider' => null])['hydra:member'];
            foreach ($results as $key => $result) {
                if ($result['organization'] !== null) {
                    unset($result[$key]);
                }
            }
        } else {
            $results = $this->eavService->getObjectList('employees', 'mrc', ['provider' => $this->commonGroundService->cleanUrl(['id' => $providerId, 'component' => 'cc', 'type' => 'organizations'])])['hydra:member'];
        }
        foreach ($results as $result) {
            $employees->add($this->createEmployeeObject($result));
        }

        return $employees;
    }

    /**
     * Fetches an employee from the EAV.
     *
     * @param string $id The id of the employee to fetch
     *
     * @throws Exception Thrown if the EAVservice is not called correctly
     *
     * @return array The resulting employee array
     */
    public function getEmployeeRaw(string $id): array
    {
        $self = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]);
        if ($this->eavService->hasEavObject($self)) {
            return $this->eavService->getObject(['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $self]);
        }

        return $this->commonGroundService->getResource($self);
    }

    /**
     * Fetches an employee from the EAV and creates a employee object for it.
     *
     * @param string $id The id of the employee to fetch
     *
     * @throws Exception Thrown if the EAVService is not called correctly
     *
     * @return Employee The resulting employee object
     */
    public function getEmployee(string $id): Employee
    {
        $result = $this->getEmployeeRaw($id);

        return $this->createEmployeeObject($result);
    }

    /**
     * Fetches an employee for a person in the contact catalogue.
     *
     * @param string $personUrl
     *
     * @return mixed|null
     */
    public function getEmployeeByPersonUrl(string $personUrl)
    {
        $result = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['person' => $personUrl])['hydra:member'];
        if (isset($result[0])) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Creates competences for an employee.
     *
     * @param array      $employeeArray The input array for the employee
     * @param string     $employeeId    The id of the employee
     * @param array|null $employee      The existing employee in the mrc
     *
     * @return array
     */
    public function createCompetences(array $employeeArray, string $employeeId, ?array $employee = []): array
    {
        if ($employee) {
            foreach ($employee['competencies'] as $key => $competence) {
                if (in_array($competence['name'], $employeeArray['targetGroupPreferences'])) {
                    $competence['grade'] = $employeeArray['hasExperienceWithTargetGroup'];
                    unset($employeeArray['targetGroupPreferences'][array_search($competence['name'], $employeeArray['targetGroupPreferences'])]);
                } else {
                    $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'competences', 'id' => $competence['id']]);
                }
            }
        }

        $competences = [];
        foreach ($employeeArray['targetGroupPreferences'] as $targetGroupPreference) {
            $competence = [
                'name'        => $targetGroupPreference,
                'description' => $employeeArray['experienceWithTargetGroupYesReason'] ?? '',
                'grade'       => $employeeArray['hasExperienceWithTargetGroup'] ? 'experienced' : 'unexperienced',
                'employee'    => "/employees/$employeeId",
            ];
            $competences[] = $this->commonGroundService->createResource($competence, ['component' => 'mrc', 'type' => 'competences'])['id'];
        }

        return $competences;
    }

    /**
     * Creates an object for the current education of an employee.
     *
     * @param array       $employeeArray The input array for the employee
     * @param string      $employeeId    The id of the employee
     * @param string|null $educationId   The id of the education if it exists already
     *
     * @throws Exception
     *
     * @return string The id of the education
     */
    public function createCurrentEducation(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                => $employeeArray['education']['name'] ?? 'CurrentEducation',
            'description'         => 'CurrentEducation',
            'startDate'           => $employeeArray['education']['startDate'] ?? null,
            'degreeGrantedStatus' => 'notGranted',
            'providesCertificate' => $employeeArray['education']['providesCertificate'] ?? null,
            'employee'            => "/employees/$employeeId",
        ];
        if ($educationId) {
            return $this->eavService->saveObject($education, ['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $educationId])])['id'];
        }

        return $this->eavService->saveObject($education, ['entityName' => 'education', 'componentCode' => 'mrc'])['id'];
    }

    /**
     * Creates an object for an unfinished education of an employee.
     *
     * @param array       $employeeArray The input array for the employee
     * @param string      $employeeId    The id of the employee
     * @param string|null $educationId   The id of the education if it exists already
     *
     * @throws Exception
     *
     * @return string The id of the education
     */
    public function createUnfinishedEducation(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                    => $employeeArray['education']['name'],
            'description'             => 'UnfinishedEducation',
            'endDate'                 => $employeeArray['education']['endDate'],
            'degreeGrantedStatus'     => 'notGranted',
            'iscedEducationLevelCode' => $employeeArray['education']['iscedEducationLevelCode'],
            'providesCertificate'     => $employeeArray['education']['providesCertificate'],
            'employee'                => "/employees/$employeeId",
        ];
        if ($educationId) {
            return $this->eavService->saveObject($education, ['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $educationId])])['id'];
        }

        return $this->eavService->saveObject($education, ['entityName' => 'education', 'componentCode' => 'mrc'])['id'];
    }

    /**
     * Creates an object for the course of an employee.
     *
     * @param array       $employeeArray The input array for the employee
     * @param string      $employeeId    The id of the employee
     * @param string|null $educationId   The id of the education if it exists already
     *
     * @throws Exception
     *
     * @return string The id of the education
     */
    public function createCourse(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                   => $employeeArray['followingCourse']['name'],
            'description'            => 'Course',
            'institution'            => $employeeArray['followingCourse']['institution'],
            'providesCertificate'    => $employeeArray['followingCourse']['providesCertificate'],
            'courseProfessionalism'  => $employeeArray['followingCourse']['courseProfessionalism'],
            'teacherProfessionalism' => $employeeArray['followingCourse']['teacherProfessionalism'],
            'employee'               => "/employees/$employeeId",
        ];
        if ($educationId) {
            return $this->eavService->saveObject($education, ['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $educationId])])['id'];
        }

        return $this->eavService->saveObject($education, ['entityName' => 'education', 'componentCode' => 'mrc'])['id'];
    }

    /**
     * Get an education for an fetched employee object.
     *
     * @param string $type       The type of education to find
     * @param array  $educations The educations of the employee
     *
     * @return string|null The resulting education
     */
    public function getEducation(string $type, array $educations): ?string
    {
        foreach ($educations as $education) {
            switch ($type) {
                case 'currentEducation':
                    if ($education['startDate'] && !$education['endDate'] && !$education['institution']) {
                        return $education['id'];
                    }
                    break;
                case 'unfinishedEducation':
                    if ($education['endDate'] && !$education['institution']) {
                        return $education['id'];
                    }
                    break;
                case 'course':
                    if ($education['institution']) {
                        return $education['id'];
                    }
                    break;
            }
        }

        return null;
    }

    // not currently used for a student
    // (see studentMutationResolver->inputToEmployee,
    // studentMutationResolver->getEmployeePropertiesFromEducationDetails &
    // studentMutationResolver->getEmployeePropertiesFromCourseDetails)

    /**
     * Creates educations for an employee.
     *
     * @param array      $employeeArray      The employee array to process
     * @param string     $employeeId         The id of the employee
     * @param array|null $existingEducations The educations already existing for the employee
     *
     * @throws Exception
     *
     * @return array
     */
    public function createEducations(array $employeeArray, string $employeeId, ?array $existingEducations = []): array
    {
        $educations = [];
        if ($employeeArray['currentEducation'] == 'YES') {
            if ($existingEducations && $existing = $this->getEducation('currentEducation', $existingEducations)) {
                $educations[] = $this->createCurrentEducation($employeeArray, $employeeId, $existing);
            } else {
                $educations[] = $this->createCurrentEducation($employeeArray, $employeeId);
            }
        }
        if ($employeeArray['currentEducation'] == 'NO_BUT_DID_EARLIER') {
            if ($existingEducations && $existing = $this->getEducation('unfinishedEducation', $existingEducations)) {
                $educations[] = $this->createUnfinishedEducation($employeeArray, $employeeId, $existing);
            } else {
                $educations[] = $this->createUnfinishedEducation($employeeArray, $employeeId);
            }
        }

        if ($employeeArray['doesCurrentlyFollowCourse']) {
            if ($existingEducations && $existing = $this->getEducation('course', $existingEducations)) {
                $educations[] = $this->createCourse($employeeArray, $employeeId, $existing);
            } else {
                $educations[] = $this->createCourse($employeeArray, $employeeId);
            }
        }

        return $educations;
    }

    /**
     * Creates educations for an employee from the student flow.
     *
     * @param string $employeeId Employee id
     * @param array  $educations Education that will be saved
     *
     * @throws \Exception
     *
     * @return array
     */
    public function createEducationsFromStudent(array $educations, string $employeeId): array
    {
        foreach ($educations as $edu) {
            $edu['employee'] = '/employees/'.$employeeId;
            if (isset($edu['id'])) {
                $edu = $this->eavService->saveObject($edu, ['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $edu['id']])]);
            } else {
                $edu = $this->eavService->saveObject($edu, ['entityName' => 'education', 'componentCode' => 'mrc']);
            }
        }

        return $educations;
    }

    /**
     * Creates interests for an employee.
     *
     * @param array      $employeeArray     The employee array to process
     * @param string     $employeeId        The id of the employee
     * @param array|null $existingInterests The interests already existing for the employee
     *
     * @return string The id of the resulting interest
     */
    public function createInterests(array $employeeArray, string $employeeId, ?array $existingInterests = []): string
    {
        foreach ($existingInterests as $existingInterest) {
            if ($existingInterest['name'] != $employeeArray['volunteeringPreference']) {
                $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'interests', 'id' => $existingInterest['id']]);
            } else {
                return $existingInterest['id'];
            }
        }
        $interest = [
            'name'        => $employeeArray['volunteeringPreference'],
            'description' => '',
            'employee'    => "/employees/$employeeId",
        ];

        return $this->commonGroundService->createResource($interest, ['component' => 'mrc', 'type' => 'interests'])['id'];
    }

    /**
     * Stores the start date of an education.
     *
     * @param array    $employeeEducation The education to process
     * @param Employee $employee          The employee to update
     *
     * @throws Exception
     *
     * @return Employee The updated employee
     */
    public function handleCurrentEducation(array $employeeEducation, Employee $employee): Employee
    {
        if ($employeeEducation['description'] == 'CurrentEducation') {
            $education = new Education();
            $education->setName($employeeEducation['name'] ?? null);
            $education->setStartDate(new DateTime($employeeEducation['startDate']) ?? null);
            $education->setDegreeGrantedStatus($employeeEducation['degreeGrantedStatus']);
            $education->setProvidesCertificate($employeeEducation['providesCertificate'] ?? null);
            $education->setTeacherProfessionalism(null);
            $education->setCourseProfessionalism(null);
            $education->setInstitution(null);
            $education->setIscedEducationLevelCode(null);
            $education->setEnddate(null);
            $education->setAmountOfHours(null);
            $education->setGroupFormation(null);

            $this->entityManager->persist($education);
            $education->setId(Uuid::fromString($employeeEducation['id']));
            $this->entityManager->persist($education);

            $employee->setCurrentEducation('YES');
            $employee->setEducation($education);
        }

        return $employee;
    }

    /**
     * Stores the start date of an education.
     *
     * @param array    $employeeEducation The education to process
     * @param Employee $employee          The employee to update
     *
     * @throws Exception
     *
     * @return Employee The updated employee
     */
    public function handleUnfinishedEducation(array $employeeEducation, Employee $employee): Employee
    {
        if ($employeeEducation['description'] == 'UnfinishedEducation') {
            $education = new Education();
            $education->setName($employeeEducation['name'] ?? null);
            $education->setEnddate(new DateTime($employeeEducation['endDate']) ?? null);
            $education->setDegreeGrantedStatus($employeeEducation['degreeGrantedStatus']);
            $education->setIscedEducationLevelCode($employeeEducation['iscedEducationLevelCode'] ?? null);
            $education->setProvidesCertificate($employeeEducation['providesCertificate'] ?? null);
            $education->setTeacherProfessionalism(null);
            $education->setCourseProfessionalism(null);
            $education->setInstitution(null);
            $education->setStartDate(null);
            $education->setAmountOfHours(null);
            $education->setGroupFormation(null);

            $this->entityManager->persist($education);
            $education->setId(Uuid::fromString($employeeEducation['id']));
            $this->entityManager->persist($education);

            $employee->setCurrentEducation('NO_BUT_DID_EARLIER');
            $employee->setEducation($education);
        }

        return $employee;
    }

    /**
     * Sets the current education data for an employee.
     *
     * @param Employee $employee  The employee to update
     * @param array    $education The education to process
     *
     * @throws Exception Thrown if the EAV is called incorrectly
     *
     * @return Employee The resulting employee
     */
    public function setCurrentEducation(Employee $employee, array $education): Employee
    {
        if ($this->eavService->hasEavObject($this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]))) {
            $education = $this->eavService->getObject(['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']])]);
        } else {
            return $employee;
        }

        $employee = $this->handleCurrentEducation($education, $employee);
        $employee = $this->handleUnfinishedEducation($education, $employee);

        return $employee;
    }

    /**
     * Sets the current course for an employee.
     *
     * @param Employee $employee  The employee to update
     * @param array    $education The education to process
     *
     * @throws Exception Thrown if the EAV is called incorrectly
     *
     * @return Employee The resulting employee
     */
    public function setCurrentCourse(Employee $employee, array $education): Employee
    {
        $education = $this->eavService->getObject(['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']])]);

        $course = new Education();
        $course->setName($education['name'] ?? null);
        $course->setInstitution($education['institution'] ?? null);
        $course->setProvidesCertificate($education['providesCertificate'] ?? null);
        $course->setCourseProfessionalism($education['courseProfessionalism'] ?? null);
        $course->setTeacherProfessionalism($education['teacherProfessionalism'] ?? null);
        $course->setGroupFormation(null);
        $course->setDegreeGrantedStatus(null);
        $course->setAmountOfHours(null);
        $course->setEnddate(null);
        $course->setIscedEducationLevelCode(null);
        $course->setStartDate(null);

        $this->entityManager->persist($course);
        $course->setId(Uuid::fromString($education['id']));
        $this->entityManager->persist($course);

        $employee->setDoesCurrentlyFollowCourse(true);
        $employee->setFollowingCourse($course);

        return $employee;
    }

    /**
     * Checks if a user exists with the contact id or username provided.
     *
     * @param string|null $contactId The contact id to find a user for
     * @param string|null $username  The username to find a user for
     *
     * @return array|null The resulting user array
     */
    public function checkIfUserExists(?string $contactId = null, ?string $username = null): ?array
    {
        if ($contactId) {
            $resources = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['person' => $contactId])['hydra:member'];
        } elseif ($username) {
            $resources = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $username])['hydra:member'];
        } else {
            throw new Error('either contactId or username should be given');
        }
        if (count($resources) > 0) {
            return $resources[0];
        }

        return null;
    }

    /**
     * Gets the user for an employee and stores the relevant data into the employee.
     *
     * @param Employee    $employee  The employee to update
     * @param string|null $contactId The contact id of the user or employee
     * @param string|null $username  The username for the user
     *
     * @return Employee The updated employee
     */
    public function getUser(Employee $employee, ?string $contactId = null, ?string $username = null): Employee
    {
        $resource = $this->checkIfUserExists($contactId, $username);
        if (isset($resource['id'])) {
            $employee->setUserId($resource['id']);
        }
        $userGroupIds = [];
        if (isset($resource['userGroups'])) {
            foreach ($resource['userGroups'] as $userGroup) {
                $userGroupIds[] = $userGroup['id'];
            }
        }
        $employee->setUserGroupIds($userGroupIds);

        return $employee;
    }

    /**
     * Updates a user for an employee.
     *
     * @param string      $userId       The id of the user to update
     * @param string|null $contact      The contact of the user to update
     * @param array       $userGroupIds The user group ids of the user to update
     *
     * @return array The resulting user
     */
    public function updateUser(string $userId, ?string $contact = null, array $userGroupIds = []): array
    {
        $user = ['userGroups' => []];
        if ($contact) {
            $user['person'] = $contact;
        }
        foreach ($userGroupIds as $userGroupId) {
            $user['userGroups'][] = "/groups/$userGroupId";
        }
        if ($user['userGroups'] == []) {
            unset($user['userGroups']);
        }

        if (str_contains($userId, 'https')) {
            $userId = $this->commonGroundService->getUuidFromUrl($userId);
        }

        return $this->commonGroundService->updateResource($user, ['component' => 'uc', 'type' => 'users', 'id' => $userId]);
    }

    /**
     * Creates an employee object for a resulting employee from the MRC.
     *
     * @param array $employeeArray The resulting array for fetching an employee
     * @param array $userRoleArray The user roles of the employee
     *
     * @throws Exception Thrown if the EAV is called incorrectly
     *
     * @return Employee The resulting employee object
     */
    public function createEmployeeObject(array $employeeArray): Employee
    {
        $contact = $this->ccService->getEavPerson($employeeArray['person']);

        $employee = new Employee();
        $employee->setPerson($this->ccService->createPersonObject($contact));
        $employee->setAvailability($contact['availability'] ? $this->availabilityService->createAvailabilityObject($contact['availability']) : null);
        $availabilityMemo = $this->availabilityService->getAvailabilityMemo($this->commonGroundService->getResource($this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $contact['id']])));
        $employee->setAvailabilityNotes($availabilityMemo['description'] ?? null);
        $employee = $this->resultToEmployeeObject($employee, $employeeArray);
        $employee = $this->subObjectsToEmployeeObject($employee, $employeeArray);
        $employee = $this->relatedObjectsToEmployeeObject($this->getUser($employee, $contact['id']), $employeeArray);

        $this->entityManager->persist($employee);
        $employee->setId(Uuid::fromString($employeeArray['id']));
        $this->entityManager->persist($employee);

        return $employee;
    }

    /**
     * Stores data in an employee object.
     *
     * @param Employee $employee       The employee to store the data in
     * @param array    $employeeResult The data to store
     *
     * @throws Exception Thrown if the eav is called incorrectly
     *
     * @return Employee The updated employee object
     */
    private function resultToEmployeeObject(Employee $employee, array $employeeResult): Employee
    {
        $employee->setIsVOGChecked($employeeResult['hasPoliceCertificate']);
        $employee->setOtherRelevantCertificates($employeeResult['relevantCertificates']);
        $employee->setGotHereVia($employeeResult['referrer']);
//        $employee->setDateCreated(new \DateTime($employeeResult['dateCreated'])); //TODO:add this back?
//        $employee->setDateModified(new \DateTime($employeeResult['dateModified']));

        return $employee;
    }

    /**
     * Stores subobjects of an mrc employee in the employee object.
     *
     * @param Employee $employee       The employee to store
     * @param array    $employeeResult The data to store
     *
     * @throws Exception
     *
     * @return Employee The resulting employee object
     */
    private function subObjectsToEmployeeObject(Employee $employee, array $employeeResult): Employee
    {
        $competences = [];
        foreach ($employeeResult['competencies'] as $competence) {
            $competences[] = $competence['name'];
        }
        $employee->setTargetGroupPreferences($competences);

        //@TODO: Dit geeft nog intermittende problemen
        $employee->setVolunteeringPreference(null);
        foreach ($employeeResult['interests'] as $interest) {
            $employee->setVolunteeringPreference($interest['name']);
        }

        $employee = $this->handleEmployeeCompetence($employeeResult, $employee);

        return $this->handleEducationType($employeeResult, $employee);
    }

    /**
     * Stores the skills of an employee in the employee object.
     *
     * @param array    $employeeResult The data to process
     * @param Employee $employee       The employee to store the data in
     *
     * @return Employee The resulting employee object
     */
    public function handleEmployeeCompetence(array $employeeResult, Employee $employee): Employee
    {
        $employee->setHasExperienceWithTargetGroup(null);
        $employee->setExperienceWithTargetGroupYesReason(null);
        foreach ($employeeResult['competencies'] as $competence) {
            if (in_array($competence['name'], $employee->getTargetGroupPreferences())) {
                $employee->setHasExperienceWithTargetGroup($competence['grade'] == 'experienced');
                $employee->setExperienceWithTargetGroupYesReason($competence['description'] != '' ? $competence['description'] : null);
            }
        }

        return $employee;
    }

    /**
     * Stores the educations for an employee.
     *
     * @param array    $employeeResult The results for the employee
     * @param Employee $employee       The employee to store the data in
     *
     * @throws Exception Thrown when the EAV is called incorrectly
     *
     * @return Employee The resulting employee
     */
    public function handleEducationType(array $employeeResult, Employee $employee): Employee
    {
        $employee->setCurrentEducation(null);
        $employee->setEducation(null);
        $employee->setDoesCurrentlyFollowCourse(null);
        $employee->setFollowingCourse(null);
        foreach ($employeeResult['educations'] as $education) {
            if (!$education['institution']) {
                $employee = $this->setCurrentEducation($employee, $education);
            } else {
                $employee = $this->setCurrentCourse($employee, $education);
            }
        }

        return $employee;
    }

    /**
     * Stores related objects in an employee object.
     *
     * @param Employee $employee       The employee to store the related objects in
     * @param array    $employeeResult The results to store
     *
     * @return Employee The resulting employee object
     */
    private function relatedObjectsToEmployeeObject(Employee $employee, array $employeeResult): Employee
    {
        $employee->setOrganizationId($this->commonGroundService->getUuidFromUrl($employeeResult['organization']));
//        $providerIdArray = explode('/', parse_url($employeeResult['provider'])['path']);
//        $employee->setOrganizationId(end($providerIdArray));

        return $employee;
    }

    /**
     * Filters out fields from a resulting user role array to the array used by the employee object.
     *
     * @param array $userRoleArray The array to convert
     *
     * @return array The converted array
     */
    public function convertUserRole(array $userRoleArray): array
    {
        return [
            'id'   => $userRoleArray['id'],
            'name' => $userRoleArray['name'],
        ];
    }

    /**
     * Returns the url for the organization of an employee.
     *
     * @param array $employeeArray
     * @param array $contact
     *
     * @return string|null the url for the organization of the employee
     */
    public function handleUserOrganizationUrl(array $employeeArray, array $contact = null): ?string
    {
        if (isset($contact['organization']['id'])) {
            return $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $contact['organization']['id']]);
        }

        $contact = $this->commonGroundService->getResource($this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $contact['id']]));
        if (isset($contact['organization'])) {
            $organizationUrl = isset($contact['organization']);
        } elseif (key_exists('organizationId', $employeeArray)) {
            $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['organizationId']]);
        } else {
            $organizationUrl = null;
        }

        return $organizationUrl;
    }

    /**
     * Sets the user groups of a user from the input array for an employee.
     *
     * @param array $employeeArray The employee input array
     * @param array $resource      The resource to add the data to
     *
     * @return array The resulting resource array
     */
    public function handleUserGroups(array $employeeArray, array $resource): array
    {
        if (key_exists('userGroupIds', $employeeArray)) {
            foreach ($employeeArray['userGroupIds'] as $userGroupId) {
                $resource['userGroups'][] = "/groups/$userGroupId";
            }
        }

        return $resource;
    }

    /**
     * Creates a user for an employee.
     *
     * @param array $employeeArray The employee input data
     * @param array $contact       The contact for the employee
     *
     * @return array The resulting user array
     */
    public function createUser(array $employeeArray, array $contact): array
    {
        $organizationUrl = $this->handleUserOrganizationUrl($employeeArray, $contact);

        $contactUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $contact['id']]);
        if (isset($employeeArray['person']['emails']['email'])) {
            $resource = [
                'username'     => $employeeArray['person']['emails']['email'],
                'person'       => $contactUrl,
                'password'     => 'ThisIsATemporaryPassword',
                'organization' => $organizationUrl ?? null,
            ];
        } elseif (isset($contact['emails'][0]['email'])) {
            $resource = [
                'username'     => $contact['emails'][0]['email'],
                'person'       => $contactUrl,
                'password'     => 'ThisIsATemporaryPassword',
                'organization' => $organizationUrl ?? null,
            ];
        }

        $resource = $this->handleUserGroups($employeeArray, $resource);

        $result = $this->commonGroundService->createResource($resource, ['component' => 'uc', 'type' => 'users']);

        $token = $this->ucService->createPasswordResetToken($resource['username'], false);
        $this->bsService->sendInvitation($token, ['username' => $resource['username'], 'contact' => $contact, 'organization' => $organizationUrl]);

        return $result;
    }

    /**
     * Gets the contact for an employee.
     *
     * @param string        $userId        The user id of the employee
     * @param array         $employeeArray The input array for the employee
     * @param Employee|null $employee      The employee object of the employee
     *
     * @throws Exception Thrownt if the EAV is called incorrectly
     *
     * @return array The resulting contact
     */
    public function getContact(string $userId, array $employeeArray, ?Employee $employee = null): array
    {
        if (isset($employeeArray['person']) && $this->commonGroundService->isResource($employeeArray['person'])) {
            return $this->commonGroundService->getResource($employeeArray['person']);
        } else {
            return $userId ? $this->ucService->updateUserContactForEmployee($userId, $employeeArray, $employee) : $this->ccService->createPersonForEmployee($employeeArray);
        }
    }

    /**
     * Saves the user for an employee.
     *
     * @param array       $employeeArray The input array for an employee
     * @param array       $contact       The contact for the employee
     * @param string|null $userId        The user id of the employee
     *
     * @return array|null The resulting user object
     */
    public function saveUser(array $employeeArray, array $contact, ?string $userId = null): ?array
    {
        if ((key_exists('userId', $employeeArray) && $employeeArray['userId']) || isset($userId) || (key_exists('email', $employeeArray) && $user = $this->checkIfUserExists(null, $employeeArray['email']))) {
            if (isset($user)) {
                $employeeArray['userId'] = $user['id'];
            } elseif (isset($userId)) {
                $employeeArray['userId'] = $userId;
            }

            $contactUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $contact['id']]);

            return $this->updateUser($employeeArray['userId'], $contactUrl, key_exists('userGroupIds', $employeeArray) ? $employeeArray['userGroupIds'] : []);
        } elseif (isset($contact['emails'][0]['email'])) {
            return $this->createUser($employeeArray, $contact);
        }

        return null;
    }

    /**
     * Sets the contact for an employee.
     *
     * @param array $employeeArray The input array for the employee
     *
     * @throws Exception
     *
     * @return array|false|mixed|string|null The resulting contact
     */
    public function setContact(array $employeeArray)
    {
        if (isset($employeeArray['person']) && $this->commonGroundService->isResource($employeeArray['person'])) {
            return $this->commonGroundService->getResource($employeeArray['person']);
        } else {
            return key_exists('userId', $employeeArray) ? $this->ucService->updateUserContactForEmployee($employeeArray['userId'], $employeeArray) : $this->ccService->createPersonForEmployee($employeeArray);
        }
    }

    /**
     * Creates an employee.
     *
     * @param array $employeeArray    The input array of the employee
     * @param array $educationsToSave The educations that need to be saved and linked to the employee
     *
     * @throws Exception
     *
     * @return array The resulting employee or raw mrc object
     */
    public function createEmployeeArray(array $employeeArray, bool $saveEducationsFromStudent = false): array
    {
        //set contact
        $contact = $this->setContact($employeeArray);

        $this->availabilityService->saveAvailabilityMemo(['description' => $employeeArray['availabilityNotes'] ?? null, 'topic' => $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $contact['id']])]);

        $this->saveUser($employeeArray, $contact);

        $resource = $this->createEmployeeResource($employeeArray, $contact, null, null);

        $resource = $this->ccService->cleanResource($resource);

        $result = $this->eavService->saveObject($resource, ['entityName' => 'employees', 'componentCode' => 'mrc']);

        if (key_exists('targetGroupPreferences', $employeeArray)) {
            $this->createCompetences($employeeArray, $result['id'], $result);
        }
        if (key_exists('volunteeringPreference', $employeeArray)) {
            $this->createInterests($employeeArray, $result['id'], $result['interests']);
        }
        if (key_exists('currentEducation', $employeeArray)) {
            $this->createEducations($employeeArray, $result['id'], $result['educations']);
        } elseif ($saveEducationsFromStudent == true) {
            $this->createEducationsFromStudent($employeeArray['educations'], $result['id']);
        }

        // Saves lastEducation, followingEducation and course for student as employee
//        if (key_exists('educations', $employeeArray)) {
        //TODO: needs a redo with the new student Education DTO subresources, maybe merge with the code for employee educations above^?
//            $this->saveEmployeeEducations($employeeArray['educations'], $result['id']);
//        }
        $result = $this->eavService->getObject(['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $result['@self']]);
        $result['userRoleArray'] = $this->handleUserRoleArray($employeeArray);

        return $result;
    }

    /**
     * Creates an employee.
     *
     * @param array $employeeArray The input array of the employee
     *
     * @throws Exception
     *
     * @return Employee The resulting employee or raw mrc object
     */
    public function createEmployee(array $employeeArray): Employee
    {
        $employee = $this->createEmployeeArray($employeeArray);

        return $this->createEmployeeObject($employee);
    }

    /**
     * Fetches a user role of an employee.
     *
     * @param array $employeeArray The employee to fetch the user group for
     *
     * @return array The resulting user role
     */
    public function handleUserRoleArray(array $employeeArray)
    {
        if (key_exists('userGroupIds', $employeeArray) and isset($employeeArray['userGroupIds'][0])) {
            $userRole = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'groups', 'id' => $employeeArray['userGroupIds'][0]]);
            $userRoleArray = $this->convertUserRole($userRole);
        } else {
            $userRoleArray = [];
        }

        return $userRoleArray;
    }

    /**
     * Saves the related user with the data from the contact of the employee.
     *
     * @param Employee $employee      The employee object the contact should relate to
     * @param array    $employeeArray The input data to update a contact
     *
     * @throws Exception
     *
     * @return array The resulting contact
     */
    public function handleRetrievingContact(Employee $employee, array $employeeArray): array
    {
        $contact = [];
        $userId = $employee->getUserId();
        if (empty($userId) && isset($employeeArray['userId'])) {
            $userId = $employeeArray['userId'];
        }

        if (isset($userId)) {
            $contact = $this->getContact($userId, $employeeArray, $employee);
            $this->saveUser($employeeArray, $contact, $userId);
        }

        if (isset($employeeArray['person'])) {
            $contact = $this->commonGroundService->getResource($employeeArray['person']);
        }

        return $contact;
    }

    /**
     * @param array       $body
     * @param string|null $id
     *
     * @return Response|null
     */
    public function checkUniqueEmployeeEmail(array $body, string $id = null): ?Response
    {
        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => str_replace('+', '%2B', $body['person']['emails']['email'])])['hydra:member'];
        if (count($users) > 0 and $users[0]['id'] != $id) {
            return new Response(
                json_encode([
                    'message' => 'A user with this email already exists!',
                    'path'    => 'person.emails.email',
                    'data'    => ['email' => $body['person']['emails']['email']],
                ]),
                Response::HTTP_CONFLICT,
                ['content-type' => 'application/json']
            );
        }

        return null;
    }

    /**
     * Updates an employee.
     *
     * @param string $id            The id of the employee
     * @param array  $employeeArray The input array of the employee
     *
     * @throws Exception
     *
     * @return Employee The resulting employee or raw mrc object
     */
    public function updateEmployee(string $id, array $employeeArray): Employee
    {
        $employee = $this->updateEmployeeArray($id, $employeeArray);

        return $this->createEmployeeObject($employee);
    }

    /**
     * Updates an employee.
     *
     * @param string $id            The id of the employee to update
     * @param array  $employeeArray The input array for the employee to update
     *
     * @throws Exception
     *
     * @return array The resulting employee
     */
    public function updateEmployeeArray(string $id, array $employeeArray): array
    {
        $employeeRaw = $this->getEmployeeRaw($id);
        $employee = $this->createEmployeeObject($employeeRaw);

        $contact = $this->handleRetrievingContact($employee, $employeeArray);

        $resource = $this->createEmployeeResource($employeeArray, $contact, $employee, $employeeRaw);

        $resource = $this->ccService->cleanResource($resource);

        $result = $this->eavService->saveObject($resource, ['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id])]);
        key_exists('targetGroupPreferences', $employeeArray) ? $this->createCompetences($employeeArray, $result['id'], $result) : null;
        key_exists('volunteeringPreference', $employeeArray) ? $this->createInterests($employeeArray, $result['id'], $result['interests']) : null;
        key_exists('currentEducation', $employeeArray) ? $this->createEducations($employeeArray, $result['id'], $result['educations']) : null;

        // Saves lastEducation, followingEducation and course for student as employee
        if (key_exists('educations', $employeeArray)) {
            //TODO: needs a redo with the new student Education DTO subresources, maybe merge with the code for employee educations above^?
//            $this->saveEmployeeEducations($employeeArray['educations'], $result['id']);
        }

        $result = $this->eavService->getObject(['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $result['@self']]);
        $result['userRoleArray'] = $this->setUserRoleArray($employeeArray);

        return $result;
    }

    /**
     * Sets the user role array of a user.
     *
     * @param array $employeeArray The employee array to fetch the user groups for
     *
     * @return array The resulting user role array
     */
    public function setUserRoleArray(array $employeeArray)
    {
        if (key_exists('userGroupIds', $employeeArray) and isset($employeeArray['userGroupIds'][0])) {
            $userRole = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'groups', 'id' => $employeeArray['userGroupIds'][0]]);
            $userRoleArray = $this->convertUserRole($userRole);
        } elseif (isset($user) && key_exists('userGroups', $user) && count($user['userGroups']) > 0) {
            $userRoleArray = $this->convertUserRole($user['userGroups'][0]);
        } else {
            $userRoleArray = [];
        }

        return $userRoleArray;
    }

    /**
     * Deletes the subobjects of an employee.
     *
     * @param array $employee
     *
     * @return bool Whether or not the action has been succesful
     */
    public function deleteSubObjects(array $employee): bool
    {
        foreach ($employee['interests'] as $interest) {
            $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'interests', 'id' => $interest['id']]);
        }
        foreach ($employee['competencies'] as $competence) {
            $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'competences', 'id' => $competence['id']]);
        }
        foreach ($employee['educations'] as $education) {
            $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]);
        }
        foreach ($employee['skills'] as $skill) {
            $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'skills', 'id' => $skill['id']]);
        }

        return true;
    }

    /**
     * Deletes an employee.
     *
     * @param string $id The id of the employee to delete
     *
     * @throws Exception
     *
     * @return bool Whether the operation has been successful or not
     */
    public function deleteEmployee(string $id): bool
    {
        $employeeArray = $this->getEmployeeRaw($id);
        $employee = $this->createEmployeeObject($employeeArray);
        $this->deleteSubObjects($employeeArray);
        $this->ucService->deleteUser($employee->getUserId());
        $this->eavService->deleteResource(null, ['component' => 'mrc', 'type' => 'employees', 'id' => $id]);
        $this->ccService->deletePerson($employee->getPerson()->getId());

        return true;
    }

    /**
     * Saves the educations of an employee.
     *
     * @param array  $educations The educations to store
     * @param string $employeeId The id of the employee the educations relate to
     *
     * @throws Exception
     */
    public function saveEmployeeEducations(array $educations, string $employeeId): void
    {
        $employeeUri = '/employees/'.$employeeId;
        foreach ($educations as $education) {
            $education['employee'] = $employeeUri;
            if (isset($education['id'])) {
                $this->eavService->saveObject($education, ['entityName' => 'education', 'componentCode' => 'mrc', 'self' => $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']])]);
            } else {
                $this->eavService->saveObject($education, ['entityName' => 'education', 'componentCode' => 'mrc']);
            }
        }
    }

    /**
     * Deletes all employees of an organization.
     *
     * @param string $ccOrganizationId The organization to delete the employees of
     *
     * @throws Exception
     *
     * @return bool Whether the operation has been successful or not
     */
    public function deleteEmployees(string $ccOrganizationId): bool
    {
        $ccOrganizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $ccOrganizationId]);
        $employees = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['organization' => $ccOrganizationUrl])['hydra:member'];

        if ($employees > 0) {
            foreach ($employees as $employee) {
                $this->deleteEmployee($employee['id']);
            }
        }

        return true;
    }

    /**
     * Creates a resource to save to the EAV for an employee.
     *
     * @param array         $employeeArray The input array of the employee
     * @param array         $contact       The contact of the employee
     * @param Employee|null $employee      The existing employee object
     * @param array|null    $employeeRaw   The raw employee object
     *
     * @return array the resulting employee resource
     */
    public function createEmployeeResource(array $employeeArray, array $contact, ?Employee $employee, ?array $employeeRaw)
    {
        return [
            'organization' => key_exists('organizationId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['organizationId']]) : $employeeRaw['organization'] ?? null,
            'person'       => $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $contact['id']]),
            //            'provider'               => key_exists('organizationId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['organizationId']]) : (isset($employee) ? $employee->getOrganizationId() : null),
            'hasPoliceCertificate'   => key_exists('isVOGChecked', $employeeArray) ? $employeeArray['isVOGChecked'] : (isset($employee) ? $employee->getIsVOGChecked() : null),
            'referrer'               => key_exists('gotHereVia', $employeeArray) ? $employeeArray['gotHereVia'] : (isset($employee) ? $employee->getGotHereVia() : null),
            'relevantCertificates'   => key_exists('otherRelevantCertificates', $employeeArray) ? $employeeArray['otherRelevantCertificates'] : (isset($employee) ? $employee->getOtherRelevantCertificates() : null),
            'trainedForJob'          => key_exists('trainedForJob', $employeeArray) ? $employeeArray['trainedForJob'] : null,
            'lastJob'                => key_exists('lastJob', $employeeArray) ? $employeeArray['lastJob'] : null,
            'dayTimeActivities'      => key_exists('dayTimeActivities', $employeeArray) ? $employeeArray['dayTimeActivities'] : null,
            'dayTimeActivitiesOther' => key_exists('dayTimeActivitiesOther', $employeeArray) ? $employeeArray['dayTimeActivitiesOther'] : null,
            'speakingLevel'          => key_exists('speakingLevel', $employeeArray) ? $employeeArray['speakingLevel'] : null,
        ];
    }
}
