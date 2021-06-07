<?php

namespace App\Service;

use App\Entity\Employee;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MrcService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private CommonGroundService $commonGroundService;
    private CCService $ccService;
    private UcService $ucService;
    private EAVService $eavService;
    private BsService $bcService;

    public function __construct(
        BsService $bcService,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        CommonGroundService $commonGroundService,
        CCService $ccService,
        UcService $ucService,
        EAVService $EAVService
    ) {
        $this->bcService = $bcService;
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->commonGroundService = $commonGroundService;
        $this->ccService = $ccService;
        $this->ucService = $ucService;
        $this->eavService = $EAVService;
    }

    public function getEmployees(?string $languageHouseId = null, ?string $providerId = null, ?array $additionalQuery = []): ArrayCollection
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
        } elseif ($education['startDate']) {
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
        if ($contactId) {
            $resources = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['person' => $contactId])['hydra:member'];
        } elseif ($username) {
            $resources = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $username])['hydra:member'];
        } else {
            throw new Error('either contactId or username should be given');
        }
        if (count($resources) > 0) {
            $employee->setUserId($resources[0]['id']);
            $userGroupIds = [];
            foreach ($resources[0]['userGroups'] as $userGroup) {
                $userGroupIds[] = $userGroup['id'];
            }
            $employee->setUserGroupIds($userGroupIds);
        }

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

    //w.i.p.
    public function createEmployeeObject(array $result, array $userRoleArray = []): Employee
    {
        if ($this->eavService->hasEavObject($result['person'])) {
            $contact = $this->eavService->getObject('people', $result['person'], 'cc');
        } else {
            $contact = $this->commonGroundService->getResource($result['person']);
        }
        $employee = new Employee();
        $employee->setGivenName($contact['givenName']);
        $employee->setAdditionalName($contact['additionalName']);
        $employee->setFamilyName($contact['familyName']);
        $employee->setGender($contact['gender'] ? $contact['gender'] : 'X');
        $employee->setDateOfBirth(new \DateTime($contact['birthday']));
        $employee->setIsVOGChecked($result['hasPoliceCertificate']);
        $employee->setOtherRelevantCertificates($result['relevantCertificates']);
        $employee->setGotHereVia($result['referrer']);
        $employee->setDateCreated(new \DateTime($result['dateCreated']));
        $employee->setDateModified(new \DateTime($result['dateModified']));
        if ($userRoleArray) {
            $employee->setUserRoles($userRoleArray);
        }

        if ($contact['contactPreference'] == 'PHONECALL' || $contact['contactPreference'] == 'WHATSAPP' || $contact['contactPreference'] == 'EMAIL') {
            $employee->setContactPreference($contact['contactPreference']);
        } else {
            $employee->setContactPreference('OTHER');
            $employee->setContactPreferenceOther($contact['contactPreference']);
        }

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

        $competences = [];
        foreach ($result['competencies'] as $competence) {
            $competences[] = $competence['name'];
        }
        $employee->setTargetGroupPreferences($competences);

        //@TODO: Dit geeft nog intermittende problemen
        foreach ($result['interests'] as $interest) {
            $employee->setVolunteeringPreference($interest['name']);
        }

        foreach ($result['skills'] as $skill) {
            if (in_array($skill['name'], $employee->getTargetGroupPreferences())) {
                $employee->setHasExperienceWithTargetGroup($skill['grade'] == 'experienced');
                $employee->setExperienceWithTargetGroupYesReason($skill['grade'] == 'experienced');
            }
        }

        foreach ($result['educations'] as $education) {
            if (!$education['institution']) {
                $employee = $this->setCurrentEducation($employee, $education);
            } else {
                $employee = $this->setCurrentCourse($employee, $education);
            }
        }
        $employee = $this->getUser($employee, $contact['@id']);
        $providerIdArray = explode('/', parse_url($result['provider'])['path']);
        $employee->setProviderId(end($providerIdArray));
        $languageHouseIdArray = explode('/', parse_url($result['organization'])['path']);
        $employee->setLanguageHouseId(end($languageHouseIdArray));

        $employee->setBiscEmployeeId($result['id']);

        if (key_exists('availability', $contact)) {
            $employee->setAvailability($contact['availability']);
        }

        $this->entityManager->persist($employee);
        $employee->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($employee);

        return $employee;
    }

    public function convertUserRole(array $userRoleArray): array
    {
        return [
            'id'   => $userRoleArray['id'],
            'name' => $userRoleArray['name'],
        ];
    }

    public function createUser(array $employeeArray, array $contact): array
    {
        if (key_exists('languageHouseId', $employeeArray)) {
            $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['languageHouseId']]);
        } elseif (key_exists('providerId', $employeeArray)) {
            $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['providerId']]);
        } else {
            $organizationUrl = null;
        }

        $resource = [
            'username'     => $employeeArray['email'],
            'person'       => $contact['@id'],
            'password'     => 'ThisIsATemporaryPassword',
            'organization' => $organizationUrl ?? null,
        ];
        if (key_exists('userGroupIds', $employeeArray)) {
            foreach ($employeeArray['userGroupIds'] as $userGroupId) {
                $resource['userGroups'][] = "/groups/$userGroupId";
            }
        }

        $result = $this->commonGroundService->createResource($resource, ['component' => 'uc', 'type' => 'users']);

        $token = $this->ucService->requestPasswordReset($resource['username'], false);
        $this->bcService->sendInvitation($resource['username'], $token, $contact, $organizationUrl);

        return $result;
    }

    public function cleanResource(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->cleanResource($value);
            } elseif (!$value) {
                unset($array[$key]);
            }
        }

        return $array;
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

    public function createEmployee(array $employeeArray, $returnMrcObject = false)
    {
        if (isset($employeeArray['person'])) {
            $contact = $this->commonGroundService->getResource($employeeArray['person']);
        } else {
            $contact = key_exists('userId', $employeeArray) ? $this->ucService->updateUserContactForEmployee($employeeArray['userId'], $employeeArray) : $this->ccService->createPersonForEmployee($employeeArray);
        }
        // TODO fix that a student has a email for creating a user so this if statement can be removed:
        if (!$returnMrcObject) {
            $this->saveUser($employeeArray, $contact);
        }

        $resource = [
            'organization'          => key_exists('languageHouseId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['languageHouseId']]) : null,
            'person'                => $contact['@id'],
            'provider'              => key_exists('providerId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['providerId']]) : null,
            'hasPoliceCertificate'  => key_exists('isVOGChecked', $employeeArray) ? $employeeArray['isVOGChecked'] : false,
            'referrer'              => key_exists('gotHereVia', $employeeArray) ? $employeeArray['gotHereVia'] : null,
            'relevantCertificates'  => key_exists('otherRelevantCertificates', $employeeArray) ? $employeeArray['otherRelevantCertificates'] : null,
            'trainedForJob'         => key_exists('trainedForJob', $employeeArray) ? $employeeArray['trainedForJob'] : null,
            'lastJob'               => key_exists('lastJob', $employeeArray) ? $employeeArray['lastJob'] : null,
            'dayTimeActivities'     => key_exists('dayTimeActivities', $employeeArray) ? $employeeArray['dayTimeActivities'] : null,
            'dayTimeActivitiesOther'=> key_exists('dayTimeActivitiesOther', $employeeArray) ? $employeeArray['dayTimeActivitiesOther'] : null,
            'speakingLevel'         => key_exists('speakingLevel', $employeeArray) ? $employeeArray['speakingLevel'] : null,
        ];

        $resource = $this->cleanResource($resource);

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
        if (key_exists('userGroupIds', $employeeArray)) {
            $userRole = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'groups', 'id' => $employeeArray['userGroupIds'][0]]);
            $userRoleArray = $this->convertUserRole($userRole);
        } else {
            $userRoleArray = [];
        }
        $result = $this->eavService->getObject('employees', $result['@self'], 'mrc');
        if ($returnMrcObject) {
            return $result;
        }

        return $this->createEmployeeObject($result, isset($userRoleArray) ? $userRoleArray : []);
    }

    public function updateEmployee(string $id, array $employeeArray, $returnMrcObject = false, $studentEmployee = false)
    {
        $employeeRaw = $this->getEmployeeRaw($id);
        $employee = $this->createEmployeeObject($employeeRaw);

        //todo remove the studentEmployee bool, also in studentMutationResolver!!! but only when the user stuff works for updating a student
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
            $user = $this->saveUser($employeeArray, $contact, $studentEmployee, $userId);
        }
        $resource = [
            'organization'          => key_exists('languageHouseId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['languageHouseId']]) : $employeeRaw['organization'],
            'person'                => $contact['@id'],
            'provider'              => key_exists('providerId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['providerId']]) : $employee->getProviderId(),
            'hasPoliceCertificate'  => key_exists('isVOGChecked', $employeeArray) ? $employeeArray['isVOGChecked'] : $employee->getIsVOGChecked(),
            'referrer'              => key_exists('gotHereVia', $employeeArray) ? $employeeArray['gotHereVia'] : $employee->getGotHereVia(),
            'relevantCertificates'  => key_exists('otherRelevantCertificates', $employeeArray) ? $employeeArray['otherRelevantCertificates'] : $employee->getOtherRelevantCertificates(),
            'trainedForJob'         => key_exists('trainedForJob', $employeeArray) ? $employeeArray['trainedForJob'] : null,
            'lastJob'               => key_exists('lastJob', $employeeArray) ? $employeeArray['lastJob'] : null,
            'dayTimeActivities'     => key_exists('dayTimeActivities', $employeeArray) ? $employeeArray['dayTimeActivities'] : null,
            'dayTimeActivitiesOther'=> key_exists('dayTimeActivitiesOther', $employeeArray) ? $employeeArray['dayTimeActivitiesOther'] : null,
            'speakingLevel'         => key_exists('speakingLevel', $employeeArray) ? $employeeArray['speakingLevel'] : null,
        ];
        $resource = $this->cleanResource($resource);

        $result = $this->eavService->saveObject($resource, 'employees', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]));
        key_exists('targetGroupPreferences', $employeeArray) ? $this->createCompetences($employeeArray, $result['id'], $result) : null;
        key_exists('volunteeringPreference', $employeeArray) ? $this->createInterests($employeeArray, $result['id'], $result['interests']) : null;

        // Saves lastEducation, followingEducation and course for student as employee
        if (key_exists('educations', $employeeArray)) {
            $this->saveEmployeeEducations($employeeArray['educations'], $result['id']);
        }

        if (key_exists('userGroupIds', $employeeArray)) {
            $userRole = $this->commonGroundService->getResource(['component' => 'uc', 'type' => 'groups', 'id' => $employeeArray['userGroupIds'][0]]);
            $userRoleArray = $this->convertUserRole($userRole);
        } elseif (isset($user) && key_exists('userGroups', $user) && count($user['userGroups']) > 0) {
            $userRoleArray = $this->convertUserRole($user['userGroups'][0]);
        } else {
            $userRoleArray = [];
        }

        $result = $this->eavService->getObject('employees', $result['@self'], 'mrc');
        if ($returnMrcObject) {
            return $result;
        }

        return $this->createEmployeeObject($result, $userRoleArray);
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
}
