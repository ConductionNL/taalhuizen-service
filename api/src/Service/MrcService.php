<?php


namespace App\Service;


use App\Entity\Employee;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        CommonGroundService $commonGroundService,
        CCService $ccService,
        UcService $ucService,
        EAVService $EAVService
    )
    {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->commonGroundService = $commonGroundService;
        $this->ccService = $ccService;
        $this->ucService = $ucService;
        $this->eavService = $EAVService;
    }

    public function getEmployees(): ArrayCollection
    {
        $employees = new ArrayCollection();
        $results = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'])['hydra:member'];
        foreach($results as $result){
            $result = $this->eavService->getObject('employee', $result['@id'], 'mrc');
            $employees->add($this->createEmployeeObject($result));
        }
        return $employees;
    }

    public function getEmployee(string $id): Employee
    {
        $result = $this->eavService->getObject('employee', $this->commonGroundService->cleanUrl(['component'=>'mrc', 'type' => 'employees', 'id' => $id]), 'mrc');
        return $this->createEmployeeObject($result);
    }

    public function createCompetences(array $employeeArray, string $employeeId, ?array $employee = []): array
    {
        if($employee){
            foreach($employee['competences'] as $key => $competence){
                if(in_array($competence['name'], $employeeArray['targetGroupPreference'])){
                    $competence['grade'] = $employeeArray['hasExperienceWithTargetGroup'];
                } else {
                    $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'competences', 'id' => $employee['competences']['id']]);
                }
            }
        } else {
            $competences = [];
            foreach($employeeArray['targetGroupPreferences'] as $targetGroupPreference){
                $competence = [
                    'name'          => $targetGroupPreference,
                    'description'   => '',
                    'grade'         => $employeeArray['hasExperienceWithTargetGroup'] ? 'experienced': 'unexperienced',
                    'employee'      => "/employees/$employeeId",
                ];
                $competences[] = $this->commonGroundService->createResource($competence, ['component' => 'mrc', 'type' => 'competences'])['id'];
            }
        }
        return $competences;
    }

    public function createCurrentEducation(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                  => $employeeArray['currentEducationYes']['name'],
            'startDate'             => $employeeArray['currentEducationYes']['dateSince'],
            'degreeGrantedStatus'   => 'notGranted',
            'providesCertificate'   => $employeeArray['doesProvideCertificate'],
            'employee'              => "/employees/$employeeId",
        ];
        if($educationId){
            return $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'educations', 'id' => $educationId]))['id'];
        }
       return $this->eavService->saveObject($education, 'education', 'mrc')['id'];
    }

    public function createUnfinishedEducation(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                      => $employeeArray['currentEducationNoButDidFollow']['name'],
            'endDate'                   => $employeeArray['currentEducationNoButDidFollow']['dateUntil'],
            'degreeGrantedStatus'       => 'notGranted',
            'iscedEducationLevelCode'   => $employeeArray['currentEducationNoButDidFollow']['level'],
            'providesCertificate'       => $employeeArray['gotCertificate'],
            'employee'                  => "/employees/$employeeId",
        ];
        if($educationId){
            return $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'educations', 'id' => $educationId]))['id'];
        }
        return $this->eavService->saveObject($education, 'education', 'mrc')['id'];
    }

    public function createCourse(array $employeeArray, string $employeeId, ?string $educationId = null): string
    {
        $education = [
            'name'                      => $employeeArray['currentlyFollowingCourseName'],
            'institute'                 => $employeeArray['currentlyFollowingCourseInstitute'],
            'endDate'                   => $employeeArray['currentEducation']['dateUntil'],
            'providesCertificate'       => $employeeArray['doesCurrentlyFollowingCourseProvideCertificate'],
            'courseProfessionalism'     => $employeeArray['currentlyFollowingCourseCourseProfessionalism'],
            'teacherProfessionalism'    => $employeeArray['currentlyFollowingCourseTeacherProfessionalism'],
            'employee'                  => "/employees/$employeeId",
        ];
        if($educationId){
            return $this->eavService->saveObject($education, 'education', 'mrc', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'educations', 'id' => $educationId]))['id'];
        }
        return $this->eavService->saveObject($education, 'education', 'mrc')['id'];
    }

    public function getEducation (string $type, array $educations): ?string
    {
        foreach($educations as $education){
            switch($type){
                case 'currentEducation':
                    if($education['startDate'] && !$education['endDate'] && !$education['institute']){
                        return $education['id'];
                    }
                    break;
                case 'unfinishedEducation':
                    if($education['endDate'] && !$education['institute']){
                        return $education['id'];
                    }
                    break;
                case 'course':
                    if($education['institute']){
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
        if($employeeArray['currentEducation'] == 'YES') {
            if($existingEducations && $existing = $this->getEducation('currentEducation', $existingEducations)){
                $educations[] = $this->createCurrentEducation($employeeArray, $employeeId, $existing);
            }
            $educations[] = $this->createCurrentEducation($employeeArray, $employeeId);
        }
        if($employeeArray['currentEducation'] == 'NO_BUT_DID_FOLLOW'){
            if($existingEducations && $existing = $this->getEducation('unfinishedEducation', $existingEducations)){
                $educations[] = $this->createCurrentEducation($employeeArray, $employeeId, $existing);
            }
            $educations[] = $this->createUnfinishedEducation($employeeArray, $employeeId);
        }

        if($employeeArray['doesCurrentlyFollowCourse']){
            if($existingEducations && $existing = $this->getEducation('course', $existingEducations)){
                $educations[] = $this->createCurrentEducation($employeeArray, $employeeId, $existing);
            }
            $educations[] = $this->createCourse($employeeArray, $employeeId);
        }
        return $educations;
    }

    public function createInterests(array $employeeArray, string $employeeId, ?array $existingInterests = []): array
    {
        foreach($existingInterests as $existingInterest){
            if(!in_array($existingInterest, $employeeArray['volunteeringPreference'])){
                $this->commonGroundService->deleteResource(null, ['component' => 'mrc', 'type' => 'interests', 'id' =>$existingInterest['id']]);
            }
        }
        $interest = [
            'name'          => $employeeArray['volunteeringPreference'],
            'description'   => '',
            'employee'      => "/employees/$employeeId",
        ];
        return $this->commonGroundService->createResource($interest, ['component' => 'mrc', 'type' => 'interests'])['id'];
    }

    public function setCurrentEducation(Employee $employee, array $education): Employee
    {
        $education = $this->eavService->getObject('education', $education['@id']);
        if($education['endDate']){
            $employee->setCurrentEducationNoButDidFollow(
                [
                    'id'                => $education['id'],
                    'dateUntil'         => $education['endDate'],
                    'level'             => $education['iscedEducationLevelCode'],
                    'gotCertificate'    => $education['providesCertificate'],
                ]
            );
            $employee->setCurrentEducation('NO_BUT_DID_FOLLOW');
        } elseif ($education['startDate']){
            $employee->setCurrentEducationNoButDidFollow(
                [
                    'id'                        => $education['id'],
                    'dateUntil'                 => $education['endDate'],
                    'name'                      => $education['name'],
                    'doesProvideCertificate'    => $education['providesCertificate'],
                ]
            );
            $employee->setCurrentEducation('YES');
        }
        return $employee;
    }

    public function setCurrentCourse(Employee $employee, array $education): Employee
    {
        $education = $this->eavService->getObject('education', $education['@id']);
        $employee->setDoesCurrentlyFollowCourse(true);
        $employee->setCurrentlyFollowingCourseName($education['name']);
        $employee->setCurrentlyFollowingCourseInstitute($education['institute']);
        $employee->setCurrentlyFollowingCourseTeacherProfessionalism($education['courseProfessionalism']);
        $employee->setCurrentlyFollowingCourseCourseProfessionalism($education['teacherProfessionalism']);
        $employee->setDoesCurrentlyFollowingCourseProvideCertificate($education['providesCertificate']);

        return $employee;
    }

    public function getUser(string $contactId, Employee $employee): Employee
    {
        $resources = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['person' => $contactId])['hydra:member'];
        if(count($resources) > 0){
            $employee->setUserId($resources[0]['id']);
            $userGroupIds = [];
            foreach($resources[0]['userGroups'] as $userGroup){
                $userGroupIds[] = $userGroup['id'];
            }
            $employee->setUserGroupIds($userGroupIds);
        }
        return $employee;
    }

    public function updateUser(string $userId, array $userGroupIds): array
    {
        $user = ['userGroups' => []];
        foreach($userGroupIds as $userGroupId){
            $user['userGroups'][] = "/groups/$userGroupId";
        }
        return $this->commonGroundService->updateResource($user, ['component' => 'uc', 'type' => 'users', 'id' => $userId]);
    }

    public function createEmployeeObject(array $result): Employee
    {
        $contact = $this->commonGroundService->getResource($result['person']);
        $employee = new Employee();
        $employee->setName($contact['givenName']);
        $employee->setAdditionalName($contact['additionalName']);
        $employee->setFamilyName($contact['familyName']);
        $employee->setGender($contact['gender']);
        $employee->setDateOfBirth($contact['birthday']);
        $employee->setIsVOGChecked($result['hasPoliceCertificate']);
        $employee->setOtherRelevantCertificates($result['relevantCertificates']);
        $employee->setGotHereVia($result['referrer']);

        if($contact['contactPreference'] == "PHONECALL" || $contact['contactPreference'] == "WHATSAPP" || $contact['contactPreference'] == "EMAIL"){
            $employee->setContactPreference($contact['contactPreference']);
        } else {
            $employee->setContactPreference("OTHER");
            $employee->setContactPreferenceOther($contact['contactPreference']);
        }

        foreach($contact['telephones'] as $telephone){
            if($telephone['name'] == 'contact telephone'){
                $employee->setContactTelephone($telephone['telephone']);
            }
            else {
                $employee->setTelephone($telephone['telephone']);
            }
        }
        foreach($contact['emails'] as $email){
            $employee->setEmail($email['email']);
        }
        foreach($contact['addresses'] as $address){
            $employee->setAddress($address['address']);
        }

        $competences = [];
        foreach($result['competences'] as $competence){
            $competences[] = $competence['name'];
        }
        $employee->setTargetGroupPreferences($competences);

        foreach($result['interests'] as $interest){
            $employee->setVolunteeringPreference($interest['name']);
        }

        foreach($result['skills'] as $skill){
            if(in_array($skill['name'], $employee->getTargetGroupPreferences())){
                $employee->setHasExperienceWithTargetGroup($skill['grade'] == 'experienced');
                $employee->setExperienceWithTargetGroupYesReason($skill['grade'] == 'experienced');
            }
        }

        foreach($result['educations'] as $education){
            if(!$education['institute']){
                $employee = $this->setCurrentEducation($employee, $education);
            } else {
                $employee = $this->setCurrentCourse($employee, $education);
            }
        }
        $employee = $this->getUser($contact['@id'], $employee);
        $aanbiederIdArray = explode('/', parse_url($result['sourceOrganization'])['path']);
        $employee->setAanbiederId(end($aanbiederIdArray));
        $taalhuisIdArray = explode('/', parse_url($result['organization'])['path']);
        $employee->setTaalhuisId(end($taalhuisIdArray));

        $employee->setBiscEmployeeId($result['id']);

        $this->entityManager->persist($employee);
        $employee->setId(Uuid::fromString($result['id']));
        $this->entityManager->persist($employee);
        return $employee;
    }

    public function createEmployee(array $employeeArray): Employee
    {
        $contact = key_exists('userId', $employeeArray) ? $this->ucService->updateUserContactForEmployee($employeeArray['userId'], $employeeArray) : $this->ccService->createPersonForEmployee($employeeArray);
        $resource = [
            'organization'          => key_exists('taalhuisId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['taalhuisId']]) : null,
            'person'                => $contact['@id'],
            'sourceOrganization'    => key_exists('aanbiederId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['aanbiederId']]) : null,
            'hasPoliceCertificate'  => key_exists('isVOGChecked', $employeeArray) ? $employeeArray['isVOGChecked'] : false,
            'referrer'              => key_exists('gotHereVia', $employeeArray) ? $employeeArray['gotHereVia'] : null,
            'relevantCertificates'  => key_exists('otherRelevantCertificates', $employeeArray) ? $employeeArray['otherRelevantCertificates'] : null,
        ];
        $result = $this->eavService->saveObject($resource, 'employee', 'mrc');
        $this->createCompetences($employeeArray, $result['id']);
        $this->createEducations($employeeArray, $result['íd']);
        $this->createInterests($employeeArray, $result['id']);
        $this->updateUser($employeeArray['userId'], $employeeArray['userGroupIds']);
        $result = $this->commonGroundService->getResource(['component' => 'mrc', 'type' => 'employees', 'id' => $result['id']]);
        return $this->createEmployeeObject($result);
    }

    public function updateEmployee(string $id, array $employeeArray): Employee
    {
        $contact = key_exists('userId', $employeeArray) ? $this->ucService->updateUserContactForEmployee($employeeArray['userId'], $employeeArray) : $this->ccService->createPersonForEmployee($employeeArray);
        $resource = [
            'organization'          => key_exists('taalhuisId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['taalhuisId']]) : null,
            'person'                => $contact['@id'],
            'sourceOrganization'    => key_exists('aanbiederId', $employeeArray) ? $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $employeeArray['aanbiederId']]) : null,
            'hasPoliceCertificate'  => key_exists('isVOGChecked', $employeeArray) ? $employeeArray['isVOGChecked'] : false,
            'referrer'              => key_exists('gotHereVia', $employeeArray) ? $employeeArray['gotHereVia'] : null,
            'relevantCertificates'  => key_exists('otherRelevantCertificates', $employeeArray) ? $employeeArray['otherRelevantCertificates'] : null,
        ];
        $result = $this->eavService->saveObject($resource, 'employee', 'mrc');
        $this->createCompetences($employeeArray, $result['id'], $result['competences']);
        $this->createEducations($employeeArray, $result['íd'], $result['educations']);
        $this->createInterests($employeeArray, $result['id'], $result['interests']);
        $this->updateUser($employeeArray['userId'], $employeeArray['userGroupIds']);
        $result = $this->commonGroundService->getResource(['component' => 'mrc', 'type' => 'employees', 'id' => $result['id']]);
        return $this->createEmployeeObject($result);
    }

    public function deleteEmployee(string $id): bool
    {
        $this->eavService->deleteObject(null, 'employee', $this->commonGroundService->cleanUrl(['component'=>'mrc', 'type' => 'employees', 'id' => $id]),'mrc');
        $this->commonGroundService->deleteResource(null, ['component'=>'mrc', 'type' => 'employees', 'id' => $id]);
        return false;
    }
}
