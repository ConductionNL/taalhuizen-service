<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use App\Entity\Group;
use App\Service\EAVService;
use App\Service\EDUService;
use App\Entity\LanguageHouse;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class GroupMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private EAVService $eavService;
    private EDUService $eduService;
    private CommonGroundService $commonGroundService;

    public function __construct(EntityManagerInterface $entityManager, EAVService $eavService, EDUService $eduService, CommonGroundService $commonGroundService){
        $this->entityManager = $entityManager;
        $this->eavService = $eavService;
        $this->eduService = $eduService;
        $this->commonGroundService = $commonGroundService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Group && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createGroup':
                return $this->createGroup($item);
            case 'updateGroup':
                return $this->updateGroup($context['info']->variableValues['input']);
            case 'removeGroup':
                return $this->deleteGroup($context['info']->variableValues['input']);
            case 'changeTeachersOfTheGroup':
                return $this->changeTeachersOfTheGroup($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createGroup(Group $groupArray): Group
    {
        $result['result'] = [];

        $group = $this->dtoToGroup($groupArray);
        $course = $this->createCourse($group);

        $result = array_merge($result,$this->checkGroupValues($group));

        if (!isset($result['errorMessage'])) {

            $result = array_merge($result, $this->makeGroup($course, $group));
//            var_dump($result);

            $resourceResult = $this->handleResult($result['group']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['group']['id']));
            $this->entityManager->persist($resourceResult);
        }

        if (isset($result['errorMessage'])){
            throw new Exception($result['errorMessage']);
        }
        return $resourceResult;
    }

    public function updateGroup(array $input): Group
    {
        $id = explode('/',$input['id']);
        $group = new Group();


        $this->entityManager->persist($group);
        return $group;
    }

    public function deleteGroup(array $group): ?Group
    {

        return null;
    }

    public function changeTeachersOfTheGroup(array $group): ?Group
    {

        return null;
    }

    public function createCourse($group){
        $organization = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $group['aanbiederId']]);
        $course = [];
        $course['name'] = 'course of '. $organization['name'];
        $course['organization'] = $organization['@id'];
        if ($this->eduService->hasProgram($organization)){
            $program = $this->eduService->getProgram($organization);
            $course['programs'][0] = $program;
        }
        $course['additionalType'] = $group['typeCourse'];
        $course['timeRequired'] = (string)$group['detailsTotalClassHours'];
        $course = $this->commonGroundService->saveResource($course,['component' => 'edu', 'type' => 'courses']);
        return $course;
    }

    public function makeGroup($course,$group,$groupId = null)
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now = $now->format('d-m-Y H:i:s');

        foreach($group as $key=>$value)
        {
            switch($key){
                case 'outComesGoal':
                    $group['goal'] = $value;
                    break;
                case 'outComesTopic':
                    $group['topic'] = $value;
                    break;
                case 'outComesTopicOther':
                    $group['topicOther'] = $value;
                    break;
                case 'outComesApplication':
                    $group['application'] = $value;
                    break;
                case 'outComesApplicationOther':
                    $group['applicationOther'] = $value;
                    break;
                case 'outComesLevel':
                    $group['level'] = $value;
                    break;
                case 'outComesLevelOther':
                    $group['levelOther'] = $value;
                    break;
                case 'detailsIsFormal':
                    $group['isFormal'] = (bool)$value;
                    break;
                case 'detailsCertificateWillBeAwarded':
                    $group['certificateWillBeAwarded'] = (bool)$value;
                    break;
                case 'detailsStartDate':
                    if($value instanceof DateTime){
                        $value = $value->format("YmdHis");
                    }
                    $group['startDate'] = $value;
                    break;
                case 'detailsEndDate':

                    if($value instanceof DateTime){
                        $value = $value->format("YmdHis");
                    }
                    $group['endDate'] = $value;
                    break;
                case 'generalLocation':
                    $group['location'] = $value;
                    break;
                case 'generalParticipantsMin':
                    $group['participantsMin'] = $value;
                    break;
                case 'generalParticipantsMax':
                    $group['participantsMax'] = $value;
                    break;
                case 'generalEvaluation':
                    $group['evaluation'] = $value;
                    break;
                default:
                    break;
            }
        }

        if (isset($groupId)){
            //update
            $group['course'] ='/courses/'.$course['id'];
//            $group['dateModified'] = $now;
//            var_dump($group);
            $group = $this->eavService->saveObject($group,'groups','edu', null,$groupId);
        }else{
            //create
            $group['course'] ='/courses/'.$course['id'];
//            var_dump($group);
            $group = $this->eavService->saveObject($group,'groups','edu');
        }

        $result['group'] = $group;
        return $result;
    }

    public function dtoToGroup(Group $resource){
        if ($resource->getGroupId()){
            $group['GroupId'] = $resource->getGroupId();
        }
        $group['aanbiederId'] = $resource->getAanbiederId();
        $group['name'] = $resource->getName();
        $group['typeCourse'] = $resource->getTypeCourse();
        $group['outComesGoal'] = $resource->getOutComesGoal();
        $group['outComesTopic'] = $resource->getOutComesTopic();
        if ($resource->getOutComesTopicOther()){
            $group['outComesTopicOther'] = $resource->getOutComesTopicOther();
        }
        $group['outComesApplication'] = $resource->getOutComesApplication();
        if ($resource->getOutComesApplicationOther()){
            $group['outComesApplicationOther'] = $resource->getOutComesApplicationOther();
        }
        $group['outComesLevel'] = $resource->getOutComesLevel();
        if ($resource->getOutComesLevelOther()){
            $group['outComesLevelOther'] = $resource->getOutComesLevelOther();
        }
        $group['detailsIsFormal'] = $resource->getDetailsIsFormal();
        $group['detailsTotalClassHours'] = $resource->getDetailsTotalClassHours();
        $group['detailsCertificateWillBeAwarded'] = $resource->getDetailsCertificateWillBeAwarded();
        if ($resource->getDetailsStartDate()) {
            $group['detailsStartDate'] = $resource->getDetailsStartDate();
        }
        if ($resource->getDetailsEndDate()){
            $group['detailsEndDate'] = $resource->getDetailsEndDate();
        }
        if ($resource->getAvailability()){
            $group['availability'] = $resource->getAvailability();
        }
        if ($resource->getAvailabilityNotes()){
            $group['availabilityNotes'] = $resource->getAvailabilityNotes();
        }
        $group['generalLocation'] = $resource->getGeneralLocation();
        if ($resource->getGeneralParticipantsMin()) {
            $group['generalParticipantsMin'] = $resource->getGeneralParticipantsMin();
        }
        if ($resource->getGeneralParticipantsMax()){
            $group['generalParticipantsMax'] = $resource->getGeneralParticipantsMax();
        }
        if ($resource->getGeneralEvaluation()){
            $group['generalEvaluation'] = $resource->getGeneralEvaluation();
        }
        $group['aanbiederEmployeeIds'] = $resource->getAanbiederEmployeeIds();

        return $group;
    }

    public function checkGroupValues($group){
        $result = [];
        if ($group['outComesTopic'] == 'OTHER' && !isset($group['outComesTopicOther'])){
            $result['errorMessage'] = 'Invalid request, outComesTopicOther is not set!';
        }elseif ($group['outComesApplication'] == 'OTHER' && !isset($group['outComesApplicationOther'])){
            $result['errorMessage'] = 'Invalid request, outComesApplicationOther is not set!';
        }elseif ($group['outComesLevel'] == 'OTHER' && !isset($group['outComesLevelOther'])){
            $result['errorMessage'] = 'Invalid request, outComesLevelOther is not set!';
        }
        $result['group'] = $group;
        return $result;
    }

    public function handleResult($group){
        $resource = new Group();
        $resource->setGroupId($group['id']);
        $resource->setAanbiederId($group['course']['organization']);
        $resource->setName($group['name']);
        $resource->setTypeCourse($group['course']['additionalType']);
        $resource->setOutComesGoal($group['goal']);
        $resource->setOutComesTopic($group['topic']);
        $resource->setOutComesTopicOther($group['topicOther']);
        $resource->setOutComesApplication($group['application']);
        $resource->setOutComesApplicationOther($group['applicationOther']);
        $resource->setOutComesLevel($group['level']);
        $resource->setOutComesLevelOther($group['levelOther']);
        $resource->setDetailsIsFormal($group['isFormal']);
        $resource->setDetailsTotalClassHours((int)$group['course']['timeRequired']);
        $resource->setDetailsCertificateWillBeAwarded($group['certificateWillBeAwarded']);
        $resource->setGeneralLocation($group['location']);
        $resource->setGeneralParticipantsMin($group['minParticipations']);
        $resource->setGeneralParticipantsMax($group['maxParticipations']);
        $resource->setDetailsEndDate(new DateTime($group['endDate']));
        $resource->setDetailsStartDate(new DateTime($group['startDate']));
        $resource->setGeneralEvaluation($group['evaluation']);
        $this->entityManager->persist($resource);
        return $resource;
    }
}
