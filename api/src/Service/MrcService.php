<?php

namespace App\Service;

use App\Entity\Employee;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MrcService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;
    private UcService $ucService;
    private EAVService $eavService;
    private BsService $bsService;

    /**
     * MrcService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param CommonGroundService    $commonGroundService
     * @param ParameterBagInterface  $parameterBag
     * @param UcService              $ucService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        ParameterBagInterface $parameterBag,
        UcService $ucService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->ucService = $ucService;
        $this->bsService = new BsService($commonGroundService, $parameterBag);
        $this->ccService = new CCService($entityManager, $commonGroundService);
        $this->eavService = new EAVService($commonGroundService);
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
        return $this->eavService->getObject('employees', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]), 'mrc');
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
                    $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'competences', 'id' => $employee['competences']['id']]);
                }
            }
        }

        $competences = [];
        foreach ($employeeArray['targetGroupPreferences'] as $targetGroupPreference) {
            $competence = [
                'name'        => $targetGroupPreference,
                'description' => '',
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
     * @return string The id of the education
     */
    public function createCurrentEducation(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                => $employeeArray['currentEducationYes']['name'],
            'startDate'           => $employeeArray['currentEducationYes']['dateSince'],
            'degreeGrantedStatus' => 'notGranted',
            'providesCertificate' => $employeeArray['currentEducationYes']['doesProvideCertificate'],
            'employee'            => "/employees/$employeeId",
        ];
        if ($educationId) {
            return $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $educationId]))['id'];
        }

        return $this->eavService->saveObject($education, 'education', 'mrc')['id'];
    }

    /**
     * Creates an object for an unfinished education of an employee.
     *
     * @param array       $employeeArray The input array for the employee
     * @param string      $employeeId    The id of the employee
     * @param string|null $educationId   The id of the education if it exists already
     *
     * @return string The id of the education
     */
    public function createUnfinishedEducation(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                    => $employeeArray['currentEducationNoButDidFollow']['name'],
            'endDate'                 => $employeeArray['currentEducationNoButDidFollow']['dateUntil'],
            'degreeGrantedStatus'     => 'notGranted',
            'iscedEducationLevelCode' => $employeeArray['currentEducationNoButDidFollow']['level'],
            'providesCertificate'     => $employeeArray['currentEducationNoButDidFollow']['gotCertificate'],
            'employee'                => "/employees/$employeeId",
        ];
        if ($educationId) {
            return $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $educationId]))['id'];
        }

        return $this->eavService->saveObject($education, 'education', 'mrc')['id'];
    }

    /**
     * Creates an object for the course of an employee.
     *
     * @param array       $employeeArray The input array for the employee
     * @param string      $employeeId    The id of the employee
     * @param string|null $educationId   The id of the education if it exists already
     *
     * @return string The id of the education
     */
    public function createCourse(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                   => $employeeArray['currentlyFollowingCourseName'],
            'institution'            => $employeeArray['currentlyFollowingCourseInstitute'],
            'providesCertificate'    => $employeeArray['doesCurrentlyFollowingCourseProvideCertificate'],
            'courseProfessionalism'  => $employeeArray['currentlyFollowingCourseCourseProfessionalism'],
            'teacherProfessionalism' => $employeeArray['currentlyFollowingCourseTeacherProfessionalism'],
            'employee'               => "/employees/$employeeId",
        ];
        if ($educationId) {
            return $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $educationId]))['id'];
        }

        return $this->eavService->saveObject($education, 'education', 'mrc')['id'];
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
        if ($employeeArray['currentEducation'] == 'NO_BUT_DID_FOLLOW') {
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
     * @param array    $education The education to process
     * @param Employee $employee  The employee to update
     *
     * @return Employee The updated employee
     */
    public function handleEducationStartDate(array $education, Employee $employee): Employee
    {
        if ($education['startDate']) {
            $employee->setCurrentEducationYes(
                [
                    'id'                     => $education['id'],
                    'dateSince'              => $education['endDate'],
                    'name'                   => $education['name'],
                    'doesProvideCertificate' => $education['providesCertificate'],
                ]
            );
            $employee->setCurrentEducation('YES');
        }

        return $employee;
    }

    /**
     * Stores the start date of an education.
     *
     * @param array    $education The education to process
     * @param Employee $employee  The employee to update
     *
     * @return Employee The updated employee
     */
    public function handleEducationEndDate(array $education, Employee $employee): Employee
    {
        if ($education['endDate']) {
            $employee->setCurrentEducationNoButDidFollow(
                [
                    'id'             => $education['id'],
                    'dateUntil'      => $education['endDate'],
                    'level'          => $education['iscedEducationLevelCode'],
                    'gotCertificate' => $education['providesCertificate'],
                ]
            );
            $employee->setCurrentEducation('NO_BUT_DID_FOLLOW');
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
            $education = $this->eavService->getObject('education', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]), 'mrc');
        } else {
            return $employee;
        }

        $employee = $this->handleEducationStartDate($education, $employee);
        $employee = $this->handleEducationEndDate($education, $employee);

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
        $education = $this->eavService->getObject('education', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]), 'mrc');
        $employee->setDoesCurrentlyFollowCourse(true);
        $employee->setCurrentlyFollowingCourseName($education['name']);
        $employee->setCurrentlyFollowingCourseInstitute($education['institution']);
        $employee->setCurrentlyFollowingCourseTeacherProfessionalism($education['courseProfessionalism']);
        $employee->setCurrentlyFollowingCourseCourseProfessionalism($education['teacherProfessionalism']);
        $employee->setDoesCurrentlyFollowingCourseProvideCertificate($education['providesCertificate']);

        return $employee;
    }

    /**
     * Creates an employee object for a resulting employee from the MRC.
     *
     * @param array $employeeArray   The resulting array for fetching an employee
     * @param array $userRoleArray   The user roles of the employee
     * @param bool  $studentEmployee Whether or not the user is both student and employee
     *
     * @throws Exception Thrown if the EAV is called incorrectly
     *
     * @return Employee The resulting employee object
     */
    public function createEmployeeObject(array $employeeArray, array $userRoleArray = [], bool $studentEmployee = false): Employee
    {
        if ($this->eavService->hasEavObject($employeeArray['person'])) {
            $contact = $this->eavService->getObject('people', $employeeArray['person'], 'cc');
        } else {
            $contact = $this->commonGroundService->getResource($employeeArray['person']);
        }

        $employee = new Employee();
        $employee = $this->contactToEmployeeObject($employee, $contact);
        $employee = $this->resultToEmployeeObject($employee, $employeeArray);
        if ($userRoleArray) {
            $employee->setUserRoles($userRoleArray);
        }
        $employee = $this->subObjectsToEmployeeObject($employee, $employeeArray);
        if (!$studentEmployee) {
            $employee = $this->relatedObjectsToEmployeeObject($this->ucService->getEmployeeUser($employee, $contact['id']), $employeeArray);
        } else {
            $employee = $this->relatedObjectsToEmployeeObject($employee, $employeeArray);
        }

        $this->entityManager->persist($employee);
        $employee->setId(Uuid::fromString($employeeArray['id']));
        $this->entityManager->persist($employee);

        return $employee;
    }

    /**
     * Stores the contact relating to an employee into the employee object.
     *
     * @param Employee $employee The employee to store the data in
     * @param array    $contact  The contact found
     *
     * @throws Exception Thrown if the EAV is not correctly called
     *
     * @return Employee The updated employee
     */
    private function contactToEmployeeObject(Employee $employee, array $contact): Employee
    {
        $employee->setGivenName($contact['givenName']);
        $employee->setAdditionalName($contact['additionalName']);
        $employee->setFamilyName($contact['familyName']);
        $employee->setGender($contact['gender'] ?: 'X');
        $employee->setDateOfBirth(new DateTime($contact['birthday']));
        if (key_exists('availability', $contact)) {
            $employee->setAvailability($contact['availability']);
        }

        if ($contact['contactPreference'] == 'PHONECALL' || $contact['contactPreference'] == 'WHATSAPP' || $contact['contactPreference'] == 'EMAIL') {
            $employee->setContactPreference($contact['contactPreference']);
        } else {
            $employee->setContactPreference('OTHER');
            $employee->setContactPreferenceOther($contact['contactPreference']);
        }

        return $this->contactObjectsToEmployeeObject($employee, $contact);
    }

    /**
     * Stores the sub objects of a contact in an employee object.
     *
     * @param Employee $employee The employee to store the data in
     * @param array    $contact  The contact to store
     *
     * @return Employee The resulting employee object
     */
    private function contactObjectsToEmployeeObject(Employee $employee, array $contact): Employee
    {
        foreach ($contact['telephones'] as $telephone) {
            if ($telephone['name'] == 'contact telephone') {
                $employee->setContactTelephone($telephone['telephone']);
            } else {
                $employee->setTelephone($telephone['telephone']);
            }
        }
        foreach ($contact['emails'] as $email) {
            $employee->setEmail($email['email']);
        }
        foreach ($contact['addresses'] as $address) {
            $employee->setAddress($address);
        }

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
        $employee->setDateCreated(new DateTime($employeeResult['dateCreated']));
        $employee->setDateModified(new DateTime($employeeResult['dateModified']));

        return $employee;
    }

    /**
     * Stores subobjects of an mrc employee in the employee object.
     *
     * @param Employee $employee       The employee to store
     * @param array    $employeeResult The data to store
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
        foreach ($employeeResult['interests'] as $interest) {
            $employee->setVolunteeringPreference($interest['name']);
        }

        $employee = $this->handleEmployeeSkills($employeeResult, $employee);
        $employee = $this->handleEducationType($employeeResult, $employee);

        return $employee;
    }

    /**
     * Stores the skills of an employee in the employee object.
     *
     * @param array    $employeeResult The data to process
     * @param Employee $employee       The employee to store the data in
     *
     * @return Employee The resulting employee object
     */
    public function handleEmployeeSkills(array $employeeResult, Employee $employee): Employee
    {
        foreach ($employeeResult['skills'] as $skill) {
            if (in_array($skill['name'], $employee->getTargetGroupPreferences())) {
                $employee->setHasExperienceWithTargetGroup($skill['grade'] == 'experienced');
                $employee->setExperienceWithTargetGroupYesReason($skill['grade'] == 'experienced');
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
    private function relatedObjectsToEmployeeObject(Employee $employee, array $employeeResult)
    {
        $providerIdArray = explode('/', parse_url($employeeResult['provider'])['path']);
        $employee->setProviderId(end($providerIdArray));
        $languageHouseIdArray = explode('/', parse_url($employeeResult['organization'])['path']);
        $employee->setLanguageHouseId(end($languageHouseIdArray));

        $employee->setBiscEmployeeId($employeeResult['id']);

        return $employee;
    }

    /**
     * Creates an employee.
     *
     * @param array $employeeArray The input array of the employee
     *
     * @throws Exception
     *
     * @return array The resulting employee or raw mrc object
     */
    public function createEmployee(array $employeeArray): array
    {
        //set contact
        $contact = $this->ucService->setEmployeeContact($employeeArray);
        $resource = $this->createEmployeeResource($employeeArray, $contact, null, null);
        $resource = $this->ccService->cleanResource($resource);
        $result = $this->eavService->saveObject($resource, 'employees', 'mrc');

        if (key_exists('targetGroupPreferences', $employeeArray)) {
            $this->createCompetences($employeeArray, $result['id'], $result);
        }
        if (key_exists('volunteeringPreference', $employeeArray)) {
            $this->createInterests($employeeArray, $result['id'], $result['interests']);
        }

        // Saves lastEducation, followingEducation and course for student as employee
        if (key_exists('educations', $employeeArray)) {
            $this->saveEmployeeEducations($employeeArray['educations'], $result['id']);
        }
        $userRoleArray = $this->handleUserRoleArray($employeeArray);
        $result = $this->eavService->getObject('employees', $result['@self'], 'mrc');

        return $result;
    }

    /**
     * Creates an employee as an object.
     *
     * @param array $employeeArray The input array
     *
     * @throws Exception Any error underway
     *
     * @return Employee The resulting employee object
     */
    public function createEmployeeToObject(array $employeeArray): Employee
    {
        $contact = $this->ucService->setEmployeeContact($employeeArray);
        $this->ucService->saveEmployeeUser($employeeArray, $contact);

        return $this->createEmployeeObject($this->createEmployee($employeeArray));
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
        if (key_exists('userGroupIds', $employeeArray)) {
            $userRole = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'groups', 'id' => $employeeArray['userGroupIds'][0]]);
            $userRoleArray = $this->ucService->convertUserRole($userRole);
        } else {
            $userRoleArray = [];
        }

        return $userRoleArray;
    }

    /**
     * Saves the related user with the data from the contact of the employee.
     *
     * @param bool     $studentEmployee Whether or not the employee is also a student
     * @param Employee $employee        The employee object the contact should relate to
     * @param array    $employeeArray   The input data to update a contact
     *
     * @throws Exception
     *
     * @return array The resulting contact
     */
    public function handleRetrievingContact(bool $studentEmployee, Employee $employee, array $employeeArray): array
    {
        if ($studentEmployee) {
            if (isset($employeeArray['person'])) {
                $contact = $this->commonGroundService->getResource($employeeArray['person']);
            }
            // if this person does not exist we should not create it here, but before we update the student employee object!
        } else {
            $userId = $employee->getUserId();
            if (empty($userId)) {
                $userId = $employeeArray['userId'];
            }
            $contact = $this->ucService->getEmployeeContact($userId, $employeeArray, $employee, $studentEmployee);
            $this->ucService->saveEmployeeUser($employeeArray, $contact, $studentEmployee, $userId);
        }

        return $contact;
    }

    /**
     * Updates an employee.
     *
     * @param string $id              The id of the employee to update
     * @param array  $employeeArray   The input array for the employee to update
     * @param bool   $studentEmployee Whether or not the employee is also a student
     *
     * @throws Exception
     *
     * @return array The resulting employee
     */
    public function updateEmployee(string $id, array $employeeArray, bool $studentEmployee = false): array
    {
        $employeeRaw = $this->getEmployeeRaw($id);
        $employee = $this->createEmployeeObject($employeeRaw, [], $studentEmployee);

        //todo remove the studentEmployee bool, also in studentMutationResolver!!! but only when the user stuff works for updating a student
        $contact = $this->handleRetrievingContact($studentEmployee, $employee, $employeeArray);

        $resource = $this->createEmployeeResource($employeeArray, $contact, $employee, $employeeRaw);

        $resource = $this->ccService->cleanResource($resource);

        $result = $this->eavService->saveObject($resource, 'employees', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]));
        key_exists('targetGroupPreferences', $employeeArray) ? $this->createCompetences($employeeArray, $result['id'], $result) : null;
        key_exists('volunteeringPreference', $employeeArray) ? $this->createInterests($employeeArray, $result['id'], $result['interests']) : null;

        // Saves lastEducation, followingEducation and course for student as employee
        if (key_exists('educations', $employeeArray)) {
            $this->saveEmployeeEducations($employeeArray['educations'], $result['id']);
        }

        //set userRoleArray
        $userRoleArray = $this->ucService->setEmployeeUserRoleArray($employeeArray);

        $result = $this->eavService->getObject('employees', $result['@self'], 'mrc');

        return $result;
    }

    /**
     * Updates an employee.
     *
     * @param string $id
     * @param array  $employeeArray
     *
     * @throws Exception
     *
     * @return Employee
     */
    public function updateEmployeeToObject(string $id, array $employeeArray): Employee
    {
        return $this->createEmployeeObject($this->updateEmployee($id, $employeeArray));
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
            $this->commonGroundService->deleteResource(null, str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $interest['@id']));
        }
        foreach ($employee['competencies'] as $competence) {
            $this->commonGroundService->deleteResource(null, str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $competence['@id']));
        }
        foreach ($employee['educations'] as $education) {
            $this->commonGroundService->deleteResource(null, str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $education['@id']));
        }
        foreach ($employee['skills'] as $skill) {
            $this->commonGroundService->deleteResource(null, str_replace('https://taalhuizen-bisc.commonground.nu/api/v1/eav', '', $skill['@id']));
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
        $this->deleteSubObjects($employeeArray);
        $employee = $this->createEmployeeObject($employeeArray);
        $this->ucService->deleteUser($employee->getUserId());
        $this->eavService->deleteObject(null, 'employees', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]), 'mrc');

        return true;
    }

    /**
     * Saves the educations of an employee.
     *
     * @param array  $educations The educations to store
     * @param string $employeeId The id of the employee the educations relate to
     */
    public function saveEmployeeEducations(array $educations, string $employeeId): void
    {
        $employeeUri = '/employees/'.$employeeId;
        foreach ($educations as $education) {
            $education['employee'] = $employeeUri;
            if (isset($education['id'])) {
                $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]));
            } else {
                $this->eavService->saveObject($education, 'education', 'mrc');
            }
        }
    }

    /**
     * Deletes all employees of an organization.
     *
     * @param string $ccOrganizationId The organization to delete the employees of
     *
     * @return bool Whether the operation has been successful or not
     */
    public function deleteEmployees(string $ccOrganizationId): bool
    {
        $employees = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['organization' => $ccOrganizationId])['hydra:member'];

        if ($employees > 0) {
            foreach ($employees as $employee) {
                $person = $this->commonGroundService->getResource($employee['person']);
                $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'people', 'id' => $person['id']]);
                $this->commonGroundService->deleteResource(null, ['component'=>'mrc', 'type'=>'employees', 'id'=>$employee['id']]);
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
            'organization'           => key_exists('languageHouseId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['languageHouseId']]) : $employeeRaw['organization'] ?? null,
            'person'                 => $contact['@id'],
            'provider'               => key_exists('providerId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['providerId']]) : (isset($employee) ? $employee->getProviderId() : null),
            'hasPoliceCertificate'   => key_exists('isVOGChecked', $employeeArray) ? $employeeArray['isVOGChecked'] : (isset($employee) ? $employee->getIsVOGChecked() : false),
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
