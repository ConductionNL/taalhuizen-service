<?php

namespace App\Service;

use App\Entity\Employee;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;

class MrcService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;
    private UcService $ucService;
    private EAVService $eavService;
    private BsService $bcService;

    public function __construct(
        BsService $bcService,
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        CCService $ccService,
        UcService $ucService,
        EAVService $EAVService
    ) {
        $this->bcService = $bcService;
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->ccService = $ccService;
        $this->ucService = $ucService;
        $this->eavService = $EAVService;
    }

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

    public function getEmployeeRaw(string $id): array
    {
        return $this->eavService->getObject('employees', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]), 'mrc');
    }

    public function getEmployee(string $id): Employee
    {
        $result = $this->getEmployeeRaw($id);

        return $this->createEmployeeObject($result);
    }

    public function getEmployeeByPersonUrl(string $personUrl)
    {
        $result = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['person' => $personUrl])['hydra:member'];
        if (isset($result[0])) {
            return $result[0];
        } else {
            return null;
        }
    }

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

    public function getEducation(string $type, array $educations): ?string
    {
        foreach ($educations as $education) {
//            var_dump($education);
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

    public function handleEducationEndDate($education, $employee)
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

    public function handleEducationStartDate($education, $employee)
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

    public function setCurrentCourse(Employee $employee, array $education): Employee
    {
//        var_Dump($education);
        $education = $this->eavService->getObject('education', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]), 'mrc');
        $employee->setDoesCurrentlyFollowCourse(true);
        $employee->setCurrentlyFollowingCourseName($education['name']);
        $employee->setCurrentlyFollowingCourseInstitute($education['institution']);
        $employee->setCurrentlyFollowingCourseTeacherProfessionalism($education['courseProfessionalism']);
        $employee->setCurrentlyFollowingCourseCourseProfessionalism($education['teacherProfessionalism']);
        $employee->setDoesCurrentlyFollowingCourseProvideCertificate($education['providesCertificate']);

        return $employee;
    }

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

    public function getUser(Employee $employee, ?string $contactId = null, ?string $username = null): Employee
    {
        $resource = $this->checkIfUserExists($contactId, $username);
        if (isset($resource['id'])) {
            $employee->setUserId($resource['id']);
        }
        $userGroupIds = [];
        foreach ($resource['userGroups'] as $userGroup) {
            $userGroupIds[] = $userGroup['id'];
        }
        $employee->setUserGroupIds($userGroupIds);

        return $employee;
    }

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

        return $this->commonGroundService->updateResource($user, ['component' => 'uc', 'type' => 'users', 'id' => $userId]);
    }

    public function createEmployeeObject(array $result, array $userRoleArray = []): Employee
    {
        if ($this->eavService->hasEavObject($result['person'])) {
            $contact = $this->eavService->getObject('people', $result['person'], 'cc');
        } else {
            $contact = $this->commonGroundService->getResource($result['person']);
        }
        $employee = new Employee();
        $employee = $this->contactToEmployeeObject($employee, $contact);
        $employee = $this->resultToEmployeeObject($employee, $result);
        if ($userRoleArray) {
            $employee->setUserRoles($userRoleArray);
        }
        $employee = $this->subObjectsToEmployeeObject($employee, $result);
        $employee = $this->relatedObjectsToEmployeeObject($this->getUser($employee, $contact['id']), $result);

        $this->entityManager->persist($employee);
        $employee->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($employee);

        return $employee;
    }

    private function contactToEmployeeObject($employee, $contact)
    {
        $employee->setGivenName($contact['givenName']);
        $employee->setAdditionalName($contact['additionalName']);
        $employee->setFamilyName($contact['familyName']);
        $employee->setGender($contact['gender'] ?: 'X');
        $employee->setDateOfBirth(new \DateTime($contact['birthday']));
        if (key_exists('availability', $contact)) {
            $employee->setAvailability($contact['availability']);
        }

        if ($contact['contactPreference'] == 'PHONECALL' || $contact['contactPreference'] == 'WHATSAPP' || $contact['contactPreference'] == 'EMAIL') {
            $employee->setContactPreference($contact['contactPreference']);
        } else {
            $employee->setContactPreference('OTHER');
            $employee->setContactPreferenceOther($contact['contactPreference']);
        }

        $this->contactObjectsToEmployeeObject($employee, $contact);

        return $employee;
    }

    private function contactObjectsToEmployeeObject($employee, $contact)
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

    private function resultToEmployeeObject($employee, $result)
    {
        $employee->setIsVOGChecked($result['hasPoliceCertificate']);
        $employee->setOtherRelevantCertificates($result['relevantCertificates']);
        $employee->setGotHereVia($result['referrer']);
        $employee->setDateCreated(new \DateTime($result['dateCreated']));
        $employee->setDateModified(new \DateTime($result['dateModified']));

        return $employee;
    }

    private function subObjectsToEmployeeObject($employee, $result)
    {
        $competences = [];
        foreach ($result['competencies'] as $competence) {
            $competences[] = $competence['name'];
        }
        $employee->setTargetGroupPreferences($competences);

        //@TODO: Dit geeft nog intermittende problemen
        foreach ($result['interests'] as $interest) {
            $employee->setVolunteeringPreference($interest['name']);
        }

        $employee = $this->handleEmployeeSkills($result, $employee);
        $employee = $this->handleEducationType($result, $employee);

        return $employee;
    }

    public function handleEmployeeSkills($result, $employee)
    {
        foreach ($result['skills'] as $skill) {
            if (in_array($skill['name'], $employee->getTargetGroupPreferences())) {
                $employee->setHasExperienceWithTargetGroup($skill['grade'] == 'experienced');
                $employee->setExperienceWithTargetGroupYesReason($skill['grade'] == 'experienced');
            }
        }

        return $employee;
    }

    public function handleEducationType($result, $employee)
    {
        foreach ($result['educations'] as $education) {
            if (!$education['institution']) {
                $employee = $this->setCurrentEducation($employee, $education);
            } else {
                $employee = $this->setCurrentCourse($employee, $education);
            }
        }

        return $employee;
    }

    private function relatedObjectsToEmployeeObject($employee, $result)
    {
        $providerIdArray = explode('/', parse_url($result['provider'])['path']);
        $employee->setProviderId(end($providerIdArray));
        $languageHouseIdArray = explode('/', parse_url($result['organization'])['path']);
        $employee->setLanguageHouseId(end($languageHouseIdArray));

        $employee->setBiscEmployeeId($result['id']);

        return $employee;
    }

    public function convertUserRole(array $userRoleArray): array
    {
        return [
            'id'   => $userRoleArray['id'],
            'name' => $userRoleArray['name'],
        ];
    }

    public function handleUserOrganizationUrl($employeeArray)
    {
        if (key_exists('languageHouseId', $employeeArray)) {
            $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['languageHouseId']]);
        } elseif (key_exists('providerId', $employeeArray)) {
            $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['providerId']]);
        } else {
            $organizationUrl = null;
        }

        return $organizationUrl;
    }

    public function handleUserGroups($employeeArray, $resource)
    {
        if (key_exists('userGroupIds', $employeeArray)) {
            foreach ($employeeArray['userGroupIds'] as $userGroupId) {
                $resource['userGroups'][] = "/groups/$userGroupId";
            }
        }

        return $resource;
    }

    public function createUser(array $employeeArray, array $contact): array
    {
        $organizationUrl = $this->handleUserOrganizationUrl($employeeArray);

        $resource = [
            'username'     => $employeeArray['email'],
            'person'       => $contact['@id'],
            'password'     => 'ThisIsATemporaryPassword',
            'organization' => $organizationUrl ?? null,
        ];

        $resource = $this->handleUserGroups($employeeArray, $resource);

        $result = $this->commonGroundService->createResource($resource, ['component' => 'uc', 'type' => 'users']);

        $token = $this->ucService->requestPasswordReset($resource['username'], false);
        $this->bcService->sendInvitation($resource['username'], $token, $contact, $organizationUrl);

        return $result;
    }

    public function getContact(string $userId, array $employeeArray, ?Employee $employee = null, bool $studentEmployee = false): array
    {
        if (isset($studentEmployee) && isset($employeeArray['person'])) {
            $contact = $this->commonGroundService->getResource($employeeArray['person']);
        // if this person does not exist we should not create it here, but before we update the student employee object!
        } else {
            $contact = $userId ? $this->ucService->updateUserContactForEmployee($userId, $employeeArray, $employee) : $this->ccService->createPersonForEmployee($employeeArray);
        }

        return $contact;
    }

    public function saveUser(array $employeeArray, array $contact, bool $studentEmployee = false, ?string $userId = null): ?array
    {
        if ((key_exists('userId', $employeeArray) && $employeeArray['userId']) || isset($userId) || (key_exists('email', $employeeArray) && $user = $this->checkIfUserExists(null, $employeeArray['email']))) {
            if (isset($user)) {
                $employeeArray['userId'] = $user['id'];
            } elseif (isset($userId)) {
                $employeeArray['userId'] = $userId;
            }

            return $this->updateUser($employeeArray['userId'], $contact['@id'], key_exists('userGroupIds', $employeeArray) ? $employeeArray['userGroupIds'] : []);
        } elseif (!$studentEmployee) {
            return $this->createUser($employeeArray, $contact);
        }

        return null;
    }

    public function setContact(array $employeeArray)
    {
        if (isset($employeeArray['person'])) {
            return  $this->commonGroundService->getResource($employeeArray['person']);
        } else {
            return key_exists('userId', $employeeArray) ? $this->ucService->updateUserContactForEmployee($employeeArray['userId'], $employeeArray) : $this->ccService->createPersonForEmployee($employeeArray);
        }
    }

    public function createEmployee(array $employeeArray, $returnMrcObject = false)
    {
        //set contact
        $contact = $this->setContact($employeeArray);

        // TODO fix that a student has a email for creating a user so this if statement can be removed:
        if (!$returnMrcObject) {
            $this->saveUser($employeeArray, $contact);
        }

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
        if ($returnMrcObject) {
            return $result;
        }

        return $this->createEmployeeObject($result, isset($userRoleArray) ? $userRoleArray : []);
    }

    public function handleUserRoleArray($employeeArray)
    {
        if (key_exists('userGroupIds', $employeeArray)) {
            $userRole = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'groups', 'id' => $employeeArray['userGroupIds'][0]]);
            $userRoleArray = $this->convertUserRole($userRole);
        } else {
            $userRoleArray = [];
        }

        return $userRoleArray;
    }

    public function handleRetrievingContact($studentEmployee, $employee, $employeeArray)
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
            $contact = $this->getContact($userId, $employeeArray, $employee, $studentEmployee);
            $this->saveUser($employeeArray, $contact, $studentEmployee, $userId);
        }

        return $contact;
    }

    public function updateEmployee(string $id, array $employeeArray, $returnMrcObject = false, $studentEmployee = false)
    {
        $employeeRaw = $this->getEmployeeRaw($id);
        $employee = $this->createEmployeeObject($employeeRaw);

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
        $userRoleArray = $this->setUserRoleArray($employeeArray);

        $result = $this->eavService->getObject('employees', $result['@self'], 'mrc');
        if ($returnMrcObject) {
            return $result;
        }

        return $this->createEmployeeObject($result, $userRoleArray);
    }

    public function setUserRoleArray($employeeArray)
    {
        if (key_exists('userGroupIds', $employeeArray)) {
            $userRole = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'groups', 'id' => $employeeArray['userGroupIds'][0]]);
            $userRoleArray = $this->convertUserRole($userRole);
        } elseif (isset($user) && key_exists('userGroups', $user) && count($user['userGroups']) > 0) {
            $userRoleArray = $this->convertUserRole($user['userGroups'][0]);
        } else {
            $userRoleArray = [];
        }

        return $userRoleArray;
    }

    public function deleteSubObjects($employee): bool
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

    public function deleteEmployee(string $id): bool
    {
        $employeeArray = $this->getEmployeeRaw($id);
        $this->deleteSubObjects($employeeArray);
        $employee = $this->createEmployeeObject($employeeArray);
        $this->ucService->deleteUser($employee->getUserId());
        $this->eavService->deleteObject(null, 'employees', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]), 'mrc');

        return false;
    }

    public function saveEmployeeEducations($educations, $employeeId): void
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

    public function deleteEmployees($ccOrganizationId): bool
    {
        $employees = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['organization' => $ccOrganizationId])['hydra:member'];

        if ($employees > 0) {
            foreach ($employees as $employee) {
                $person = $this->commonGroundService->getResource($employee['person']);
                $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'people', 'id' => $person['id']]);
                $this->commonGroundService->deleteResource(null, ['component'=>'mrc', 'type'=>'employees', 'id'=>$employee['id']]);
            }
        }

        return false;
    }

    public function createEmployeeResource(array $employeeArray, array $contact, $employee, $employeeRaw)
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
