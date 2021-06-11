<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Group;
use App\Service\EAVService;
use App\Service\EDUService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class GroupMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private EAVService $eavService;
    private EDUService $eduService;
    private CommonGroundService $commonGroundService;

    public function __construct(EntityManagerInterface $entityManager, EAVService $eavService, EDUService $eduService, CommonGroundService $commonGroundService)
    {
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
        /**@todo: changeTeacher,
         * done: create, update, remove
         */
        switch ($context['info']->operation->name->value) {
            case 'createGroup':
                return $this->createGroup($item);
            case 'updateGroup':
                return $this->updateGroup($context['info']->variableValues['input']);
            case 'removeGroup':
                return $this->removeGroup($context['info']->variableValues['input']);
            case 'changeTeachersOfTheGroup':
                return $this->changeTeachersOfTheGroup($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createGroup(Group $input): Group
    {
        $result['result'] = [];

        $group = $this->dtoToGroup($input);
        $course = $this->createCourse($group);

        $result = array_merge($result, $this->checkGroupValues($group));

        if (!isset($result['errorMessage'])) {
            $result = array_merge($result, $this->makeGroup($course, $result['group']));

            $resourceResult = $this->eduService->convertGroupObject($result['group'], $group['aanbiederId']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['group']['id']));
            $this->entityManager->persist($resourceResult);
        }

        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }

    public function updateGroup(array $groupArray): Group
    {
        $id = explode('/', $groupArray['id']);
        $id = end($id);
        $result['result'] = [];

        $groupArray = array_merge(
            $this->dtoToGroup($this->eduService->getGroup($id)),
            $groupArray
        );
        $course = $this->createCourse($groupArray);
        $result = array_merge($result, $this->checkGroupValues($groupArray));

        if (!isset($result['errorMessage'])) {
            $result = array_merge($result, $this->makeGroup($course, $result['group'], $id));

            $resourceResult = $this->eduService->convertGroupObject($result['group'], $groupArray['aanbiederId']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['group']['id']));
            $this->entityManager->persist($resourceResult);
        }

        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }

    public function removeGroup($group): ?Group
    {
        if (isset($group['id'])) {
            $groupId = explode('/', $group['id']);
            if (is_array($groupId)) {
                $groupId = end($groupId);
            }
        } else {
            throw new Exception('No id was specified!');
        }

        $this->eduService->deleteGroup($groupId);

        return null;
    }

    public function changeTeachersOfTheGroup($input): ?Group
    {
        if (isset($input['id'])) {
            $groupId = explode('/', $input['id']);
            if (is_array($groupId)) {
                $groupId = end($groupId);
            }
        } else {
            throw new Exception('No id was specified!');
        }
        if (isset($input['aanbiederEmployeeIds'])) {
            $employeeIds = $input['aanbiederEmployeeIds'];
        } else {
            throw new Exception('No EmployeeIds were specified!');
        }

        return $this->eduService->changeGroupTeachers($groupId, $employeeIds);
    }

    public function createCourse($group)
    {
        $organization = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $group['aanbiederId']]);
        $course = [];
        $course['name'] = 'course of '.$organization['name'];
        $course['organization'] = $organization['@id'];
        if ($this->eduService->hasProgram($organization)) {
            $program = $this->eduService->getProgram($organization);
            $course['programs'][0] = '/programs/'.$program['id'];
        }
        $course['additionalType'] = $group['typeCourse'];
        $course['timeRequired'] = (string) $group['totalClassHours'];

        return $this->commonGroundService->saveResource($course, ['component' => 'edu', 'type' => 'courses']);
    }

    public function makeGroup($course, $group, $groupId = null)
    {
        if (isset($groupId)) {
            //update
            $group['course'] = '/courses/'.$course['id'];
            $group = $this->eavService->saveObject($group, 'groups', 'edu', $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $groupId]));
        } else {
            //create
            $group['course'] = '/courses/'.$course['id'];
            $group = $this->eavService->saveObject($group, 'groups', 'edu');
        }
        $result['group'] = $group;

        return $result;
    }

    public function dtoToGroup(Group $resource)
    {
        $group = [];
        if ($resource->getGroupId()) {
            $group['GroupId'] = $resource->getGroupId();
        }
        //set aanbieder
        $group = $this->setAanbieder($resource, $group);
        $group['name'] = $resource->getName();
        $group['typeCourse'] = $resource->getTypeCourse();
        $group['goal'] = $resource->getOutComesGoal();
        $group['topic'] = $resource->getOutComesTopic();
        $group['application'] = $resource->getOutComesApplication();
        $group['level'] = $resource->getOutComesLevel();
        //set outcomes other
        $group = $this->setOutcomesOther($resource, $group);
        $group['isFormal'] = (bool) $resource->getDetailsIsFormal();
        $group['totalClassHours'] = $resource->getDetailsTotalClassHours();
        $group['certificateWillBeAwarded'] = $resource->getDetailsCertificateWillBeAwarded();
        // set detail dates
        $group = $this->setDetailDates($resource, $group);
        // set availabilities
        $group = $this->setGroupAvailabilities($resource, $group);
        $group['location'] = $resource->getGeneralLocation();
        //set participant limits
        $group = $this->setParticipantLimits($resource, $group);
        if ($resource->getGeneralEvaluation()) {
            $group['evaluation'] = $resource->getGeneralEvaluation();
        }
        $group['mentors'] = $resource->getAanbiederEmployeeIds();

        return $group;
    }

    public function setAanbieder($resource, $group)
    {
        $aanbieder = explode('/', $resource->getAanbiederId());
        if (is_array($aanbieder)) {
            $aanbieder = end($aanbieder);
        }
        $group['aanbiederId'] = $aanbieder;

        return $group;
    }

    public function setOutcomesOther($resource, $group)
    {
        if ($resource->getOutComesTopicOther()) {
            $group['topicOther'] = $resource->getOutComesTopicOther();
        }
        if ($resource->getOutComesApplicationOther()) {
            $group['applicationOther'] = $resource->getOutComesApplicationOther();
        }
        if ($resource->getOutComesLevelOther()) {
            $group['levelOther'] = $resource->getOutComesLevelOther();
        }

        return $group;
    }

    public function setDetailDates($resource, $group)
    {
        if ($resource->getDetailsStartDate()) {
            $group['startDate'] = $resource->getDetailsStartDate();
        }
        if ($resource->getDetailsEndDate()) {
            $group['endDate'] = $resource->getDetailsEndDate();
        }

        return $group;
    }

    public function setParticipantLimits($resource, $group)
    {
        if ($resource->getGeneralParticipantsMin()) {
            $group['participantsMin'] = $resource->getGeneralParticipantsMin();
        }
        if ($resource->getGeneralParticipantsMax()) {
            $group['participantsMax'] = $resource->getGeneralParticipantsMax();
        }

        return $group;
    }

    public function setGroupAvailabilities($resource, $group)
    {
        if ($resource->getAvailability()) {
            $group['availability'] = $resource->getAvailability();
        }
        if ($resource->getAvailabilityNotes()) {
            $group['availabilityNotes'] = $resource->getAvailabilityNotes();
        }

        return $group;
    }

    public function checkGroupValues($group)
    {
        $result = [];
        if ($group['topic'] == 'OTHER' && !isset($group['topicOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesTopicOther is not set!';
        } elseif ($group['application'] == 'OTHER' && !isset($group['applicationOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesApplicationOther is not set!';
        } elseif ($group['level'] == 'OTHER' && !isset($group['levelOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesLevelOther is not set!';
        }
        if ($group['startDate'] instanceof DateTime) {
            $group['startDate'] = $group['startDate']->format('YmdHis');
        }
        if ($group['endDate'] instanceof DateTime) {
            $group['endDate'] = $group['endDate']->format('YmdHis');
        }
        $result['group'] = $group;

        return $result;
    }
}
