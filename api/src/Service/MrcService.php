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
    )
    {
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
            $results = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], array_merge(['organization' => $this->commonGroundService->cleanUrl(['id' => $languageHouseId, 'component' => 'cc', 'type' => 'organizations']), 'limit' => 1000], $additionalQuery))['hydra:member'];
        } elseif (!$providerId) {
            $results = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], array_merge(['limit' => 1000], $additionalQuery))['hydra:member'];
            foreach ($results as $key => $result) {
                if ($result['organization'] !== null) {
                    unset($result[$key]);
                }
            }
        } else {
            $results = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], array_merge(['limit' => 1000], $additionalQuery))['hydra:member'];
        }
        foreach ($results as $result) {
            try {
                $result = $this->eavService->getObject('employees', $result['@id'], 'mrc');
                if ($providerId && strpos($result['provider'], $providerId) === false) {
                    continue;
                }
                $employees->add($this->createEmployeeObject($result));
            } catch (\Exception $e) {
                continue;
            }
        }
        return $employees;
    }

    public function getEmployee(string $id): Employee
    {
        $result = $this->eavService->getObject('employees', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]), 'mrc');
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
                'name' => $targetGroupPreference,
                'description' => '',
                'grade' => $employeeArray['hasExperienceWithTargetGroup'] ? 'experienced' : 'unexperienced',
                'employee' => "/employees/$employeeId",
            ];
            $competences[] = $this->commonGroundService->createResource($competence, ['component' => 'mrc', 'type' => 'competences'])['id'];
        }
        return $competences;
    }

    public function createCurrentEducation(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {

        $education = [
            'name' => $employeeArray['currentEducationYes']['name'],
            'startDate' => $employeeArray['currentEducationYes']['dateSince'],
            'degreeGrantedStatus' => 'notGranted',
            'providesCertificate' => $employeeArray['currentEducationYes']['doesProvideCertificate'],
            'employee' => "/employees/$employeeId",
        ];
        if ($educationId) {
            return $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $educationId]))['id'];
        }
        return $this->eavService->saveObject($education, 'education', 'mrc')['id'];
    }

    public function createUnfinishedEducation(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name' => $employeeArray['currentEducationNoButDidFollow']['name'],
            'endDate' => $employeeArray['currentEducationNoButDidFollow']['dateUntil'],
            'degreeGrantedStatus' => 'notGranted',
            'iscedEducationLevelCode' => $employeeArray['currentEducationNoButDidFollow']['level'],
            'providesCertificate' => $employeeArray['currentEducationNoButDidFollow']['gotCertificate'],
            'employee' => "/employees/$employeeId",
        ];
        if ($educationId) {
            return $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $educationId]))['id'];
        }
        return $this->eavService->saveObject($education, 'education', 'mrc')['id'];
    }

    public function createCourse(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name' => $employeeArray['currentlyFollowingCourseName'],
            'institution' => $employeeArray['currentlyFollowingCourseInstitute'],
            'providesCertificate' => $employeeArray['doesCurrentlyFollowingCourseProvideCertificate'],
            'courseProfessionalism' => $employeeArray['currentlyFollowingCourseCourseProfessionalism'],
            'teacherProfessionalism' => $employeeArray['currentlyFollowingCourseTeacherProfessionalism'],
            'employee' => "/employees/$employeeId",
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
            'name' => $employeeArray['volunteeringPreference'],
            'description' => '',
            'employee' => "/employees/$employeeId",
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
                    'id' => $education['id'],
                    'dateUntil' => $education['endDate'],
                    'level' => $education['iscedEducationLevelCode'],
                    'gotCertificate' => $education['providesCertificate'],
                ]
            );
            $employee->setCurrentEducation('NO_BUT_DID_FOLLOW');
        } elseif ($education['startDate']) {
            $employee->setCurrentEducationYes(
                [
                    'id' => $education['id'],
                    'dateSince' => $education['endDate'],
                    'name' => $education['name'],
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
        return $this->commonGroundService->updateResource($user, ['component' => 'uc', 'type' => 'users', 'id' => $userId]);
    }

    public function createEmployeeObject(array $result): Employee
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

        if ($contact['contactPreference'] == "PHONECALL" || $contact['contactPreference'] == "WHATSAPP" || $contact['contactPreference'] == "EMAIL") {
            $employee->setContactPreference($contact['contactPreference']);
        } else {
            $employee->setContactPreference("OTHER");
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

    public function createUser(array $employeeArray, array $contact)
    {
        $resource = [
            'username' => $employeeArray['email'],
            'person' => $contact['@id'],
            'password' => 'ThisIsATemporaryPassword',
        ];
        if (key_exists('userGroupIds', $employeeArray)) {
            foreach ($employeeArray['userGroupIds'] as $userGroupId) {
                $user['userGroups'][] = "/groups/$userGroupId";
            }
        }
        $result = $this->commonGroundService->createResource($resource, ['component' => 'uc', 'type' => 'users']);

        $token = $this->ucService->requestPasswordReset($resource['username']);
        $this->bcService->sendInvitation($resource['username'], $token, $contact);

        return $result['id'];
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

    public function createEmployee(array $employeeArray): Employee
    {
        $contact = key_exists('userId', $employeeArray) ? $this->ucService->updateUserContactForEmployee($employeeArray['userId'], $employeeArray) : $this->ccService->createPersonForEmployee($employeeArray);
        $resource = [
            'organization'          => key_exists('languageHouseId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['languageHouseId']]) : null,
            'person'                => $contact['@id'],
            'provider'              => key_exists('providerId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['providerId']]) : null,
            'hasPoliceCertificate'  => key_exists('isVOGChecked', $employeeArray) ? $employeeArray['isVOGChecked'] : false,
            'referrer'              => key_exists('gotHereVia', $employeeArray) ? $employeeArray['gotHereVia'] : null,
            'relevantCertificates'  => key_exists('otherRelevantCertificates', $employeeArray) ? $employeeArray['otherRelevantCertificates'] : null,
        ];

        $resource = $this->cleanResource($resource);

        $result = $this->eavService->saveObject($resource, 'employees', 'mrc');
        if (key_exists('targetGroupPreferences', $employeeArray)) {
            $this->createCompetences($employeeArray, $result['id'], $result['educations']);
        }
        if (key_exists('currentEducation', $employeeArray)) {
            $this->createEducations($employeeArray, $result['id'], $result['educations']);
        }
        if (key_exists('volunteeringPreference', $employeeArray)) {
            $this->createInterests($employeeArray, $result['id'], $result['interests']);
        }
        if ((key_exists('userId', $employeeArray) && $employeeArray['userId']) || $user = $this->checkIfUserExists(null, $employeeArray['email'])) {
            if (isset($user)) {
                $employeeArray['userId'] = $user['id'];
            }
            $this->updateUser($employeeArray['userId'], $contact['@id'], key_exists('userGroupIds', $employeeArray) ? $employeeArray['userGroupIds'] : []);
        } else {
            $this->createUser($employeeArray, $contact);
        }
        $result = $this->eavService->getObject('employees', $result['@self'], 'mrc');
        return $this->createEmployeeObject($result);
    }

    public function updateEmployee(string $id, array $employeeArray): Employee
    {
        //@TODO: keep CC Database clean
        $contact = key_exists('userId', $employeeArray) ? $this->ucService->updateUserContactForEmployee($employeeArray['userId'], $employeeArray) : $this->ccService->createPersonForEmployee($employeeArray);
        $resource = [
            'organization'          => key_exists('languageHouseId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['languageHouseId']]) : null,
            'person'                => $contact['@id'],
            'provider'              => key_exists('providerId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['providerId']]) : null,
            'hasPoliceCertificate'  => key_exists('isVOGChecked', $employeeArray) ? $employeeArray['isVOGChecked'] : false,
            'referrer'              => key_exists('gotHereVia', $employeeArray) ? $employeeArray['gotHereVia'] : null,
            'relevantCertificates'  => key_exists('otherRelevantCertificates', $employeeArray) ? $employeeArray['otherRelevantCertificates'] : null,
        ];
        $resource = $this->cleanResource($resource);

        $result = $this->eavService->saveObject($resource, 'employees', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]));
        if (key_exists('targetGroupPreferences', $employeeArray)) {
            $this->createCompetences($employeeArray, $result['id'], $result);
        }
        if (key_exists('currentEducation', $employeeArray)) {
            $this->createEducations($employeeArray, $result['id'], $result['educations']);
        }
        if (key_exists('volunteeringPreference', $employeeArray)) {
            $this->createInterests($employeeArray, $result['id'], $result['interests']);
        }
        if ((key_exists('userId', $employeeArray) && $employeeArray['userId']) || $user = $this->checkIfUserExists(null, $employeeArray['email'])) {
            if (isset($user)) {
                $employeeArray['userId'] = $user['id'];
            }
            $this->updateUser($employeeArray['userId'], $contact['@id'], key_exists('userGroupIds', $employeeArray) ? $employeeArray['userGroupIds'] : []);
        } else {
            $this->createUser($employeeArray, $contact);
        }
        if (key_exists('dayTimeActivitiesOther', $employeeArray)) {
            $employeeArray['dayTimeActivities'] = [$employeeArray['dayTimeActivitiesOther']];
        }
        if (key_exists('dayTimeActivities', $employeeArray)) {
            $this->createDayTimeActivities($employeeArray);
        }
        if (key_exists('lastJob', $employeeArray)) {
            $this->createLastJob($employeeArray, $result);
        }

        $result = $this->eavService->getObject('employees', $result['@self'], 'mrc');
        return $this->createEmployeeObject($result);
    }

    public function createDayTimeActivities(array $employeeArray)
    {
        foreach ($employeeArray['interests'] as $interest) {
            if ($interest['name'] == 'dayTimeActivity') {
                $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'interests', 'id' => $interest['id']]);
            }
        }
        foreach ($employeeArray['dayTimeActivities'] as $newActivity) {
            $interest = [
                'name' => 'dayTimeActivity',
                'description' => $newActivity,
                'employee' => "/employees/" . $employeeArray['id'],
            ];
            $this->commonGroundService->createResource($interest, ['component' => 'mrc', 'type' => 'interests']);
        }
    }

    public function deleteEmployee(string $id): bool
    {
        $this->eavService->deleteObject(null, 'employees', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]), 'mrc');
        $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'employees', 'id' => $id]);
        return false;
    }

    private function createLastJob(array $employeeArray, array $savedEmployee)
    {
        if (isset($result['jobFunctions'])) {
            foreach ($result['jobFunctions'] as $job) {
                if ($job['name'] == 'lastJob') {
                    $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'job_functions', 'id' => $job['id']]);
                }
            }
        }
        $jobFunction = [
            'name' => 'lastJob',
            'description' => $employeeArray['lastJob'],
            'employee' => '/employees/' . $employeeArray['id']
        ];
        $this->commonGroundService->createResource($jobFunction, ['component' => 'mrc', 'type' => 'job_functions']);

    }
}
