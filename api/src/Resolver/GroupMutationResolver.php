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

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService)
    {
        $this->entityManager = $entityManager;
        $this->eavService = new EAVService($commonGroundService);
        $this->eduService = new EDUService($commonGroundService);
        $this->commonGroundService = $commonGroundService;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Group && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
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

    /**
     * Creates a new Group.
     *
     * @param Group $input the input data for the new Group.
     *
     * @throws Exception
     *
     * @return Group the created Group
     */
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

    /**
     * Updates an existing Group.
     *
     * @param array $groupArray the input data for a Group.
     *
     * @throws Exception
     *
     * @return Group the updated Group.
     */
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

    /**
     * Deletes a Group.
     *
     * @param array $group the input data for a Group.
     *
     * @throws Exception
     *
     * @return Group|null null if successful.
     */
    public function removeGroup(array $group): ?Group
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

    /**
     * Changes the teachers of a group.
     *
     * @param array $input the input data for a Group.
     *
     * @throws Exception
     *
     * @return Group|null the updated Group.
     */
    public function changeTeachersOfTheGroup(array $input): ?Group
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

    /**
     * Creates an edu/course for a group.
     *
     * @param array $group the input data for a Group.
     *
     * @return array the created edu/course.
     */
    public function createCourse(array $group): array
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

    /**
     * Makes a new edu/group object with the eav-component. Or updates an existing one.
     *
     * @param array       $course  the course this group is connected to.
     * @param array       $group   the input data for a Group.
     * @param string|null $groupId the id of an already existing group, to update it.
     *
     * @return array the result array, containing the created or updated group.
     */
    public function makeGroup(array $course, array $group, string $groupId = null): array
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

    /**
     * This function converts the input data for creating a Group from the DTO to a valid Group body.
     *
     * @param Group $resource the Group DTO input.
     *
     * @return array a valid group body.
     */
    public function dtoToGroup(Group $resource): array
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

    /**
     * This function gets the aanbieder id from the given input Group DTO and adds it to the given group array.
     *
     * @param Group $resource the Group DTO input.
     * @param array $group    the group array.
     *
     * @return array the group body now containing the aanbiederId.
     */
    public function setAanbieder(Group $resource, array $group): array
    {
        $aanbieder = explode('/', $resource->getAanbiederId());
        if (is_array($aanbieder)) {
            $aanbieder = end($aanbieder);
        }
        $group['aanbiederId'] = $aanbieder;

        return $group;
    }

    /**
     * This function checks if the topic, application and level 'other' inputs are set when creating a new Group.
     * For each one that is set, it will be added to the group array.
     *
     * @param Group $resource the Group DTO input.
     * @param array $group    the group array.
     *
     * @return array a valid group body containing the other options if they where set in the input.
     */
    public function setOutcomesOther(Group $resource, array $group): array
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

    /**
     * This function checks if the startDate and endDate inputs are set when creating a new Group.
     * For each one that is set, it will be added to the group array.
     *
     * @param Group $resource the Group DTO input.
     * @param array $group    the group array.
     *
     * @return array a valid group body containing the startDate and/or endDate if they where set in the input.
     */
    public function setDetailDates(Group $resource, array $group): array
    {
        if ($resource->getDetailsStartDate()) {
            $group['startDate'] = $resource->getDetailsStartDate();
        }
        if ($resource->getDetailsEndDate()) {
            $group['endDate'] = $resource->getDetailsEndDate();
        }

        return $group;
    }

    /**
     * This function checks if the participantsMin and participantsMax inputs are set when creating a new Group.
     * For each one that is set, it will be added to the group array.
     *
     * @param Group $resource the Group DTO input.
     * @param array $group    the group array.
     *
     * @return array a valid group body containing the participantsMin and/or participantsMax if they where set in the input.
     */
    public function setParticipantLimits(Group $resource, array $group): array
    {
        if ($resource->getGeneralParticipantsMin()) {
            $group['participantsMin'] = $resource->getGeneralParticipantsMin();
        }
        if ($resource->getGeneralParticipantsMax()) {
            $group['participantsMax'] = $resource->getGeneralParticipantsMax();
        }

        return $group;
    }

    /**
     * This function checks if the availability and availabilityNotes inputs are set when creating a new Group.
     * For each one that is set, it will be added to the group array.
     *
     * @param Group $resource the Group DTO input.
     * @param array $group    the group array.
     *
     * @return array a valid group body containing the availability and/or availabilityNotes if they where set in the input.
     */
    public function setGroupAvailabilities(Group $resource, array $group): array
    {
        if ($resource->getAvailability()) {
            $group['availability'] = $resource->getAvailability();
        }
        if ($resource->getAvailabilityNotes()) {
            $group['availabilityNotes'] = $resource->getAvailabilityNotes();
        }

        return $group;
    }

    /**
     * This function checks if the given group body is valid to use to create or update a Group.
     *
     * @param array $group the body of a Group.
     *
     * @return array the result array containing the group or an errorMessage.
     */
    public function checkGroupValues(array $group): array
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
