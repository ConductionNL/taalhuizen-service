<?php

namespace App\Service;

use App\Entity\Group;
use App\Entity\StudentDossierEvent;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Ramsey\Uuid\Uuid;

class EDUService
{
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private EntityManagerInterface $entityManager;

    /**
     * EDUService constructor.
     *
     * @param CommonGroundService    $commonGroundService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        CommonGroundService $commonGroundService,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = new EAVService($commonGroundService);
    }

    /**
     * This function updates or creates a edu/participant with the given body.
     *
     * @param array       $body           Array with data from the edu/participant
     * @param string|null $participantUrl Url of the edu/participant
     *
     * @throws \Exception
     *
     * @return array A edu/participant is returned from the EAV
     */
    public function saveEavParticipant(array $body, $participantUrl = null): array
    {
        // Save the edu/participant in EAV
        if (isset($participantUrl)) {
            // Update
            $person = $this->eavService->saveObject($body, ['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $participantUrl]);
        } else {
            // Create
            $person = $this->eavService->saveObject($body, ['entityName' => 'participants', 'componentCode' => 'edu']);
        }

        return $person;
    }

    /**
     * This function updates or creates a edu/result with the given body.
     *
     * @param array       $body      Array with data from the edu/result
     * @param string|null $resultUrl Url of the edu/result
     *
     * @throws \Exception
     *
     * @return array A edu/result is returned from the EAV
     */
    public function saveEavResult(array $body, $resultUrl = null): array
    {
        // Save the edu/result in EAV
        if (isset($resultUrl)) {
            // Update
            $result = $this->eavService->saveObject($body, ['entityName' => 'results', 'componentCode' => 'edu', 'self' => $resultUrl]);
        } else {
            // Create
            $result = $this->eavService->saveObject($body, ['entityName' => 'results', 'componentCode' => 'edu']);
        }

        return $result;
    }

    /**
     * This function updates or creates a edu/program that belongs to the given organization.
     *
     * @param array      $organization Array that holds data about the organization this program belongs to
     * @param bool|false $update       Bool if this program should be updated or not
     *
     * @return array|false|mixed|string|null A edu/program is returned from the EAV
     */
    public function saveProgram(array $organization, bool $update = false)
    {
        $program = $this->commonGroundService->getResourceList(['component' => 'edu', 'type'=>'programs'], ['provider', $organization['@id']])['hydra:member'];
        $program['name'] = $organization['name'];
        $program['provider'] = $organization['@id'];

        if ($update) {
            $program = $program[0];
            $program = $this->commonGroundService->updateResource($program, ['component' => 'edu', 'type'=>'programs', 'id' => $program['id']]);
        } else {
            $program = $this->commonGroundService->saveResource($program, ['component' => 'edu', 'type'=>'programs']);
        }

        return $program;
    }

    /**
     * This function fetches a single program that belongs to the given organization.
     *
     * @param array $organization The organization a program is getting fetched for
     *
     * @return mixed A program is returned from the edu component
     */
    public function getProgram(array $organization)
    {
        return $result = $this->commonGroundService->getResourceList(['component' => 'edu', 'type'=>'programs'], ['provider' => $organization['@id']])['hydra:member'][0];
    }

    /**
     * This function checks if the given organization has a program or not.
     *
     * @param array $organization The organization that is getting checked for if it has a program or not
     *
     * @return bool Returns a true or false depending if the given organization has a program or not
     */
    public function hasProgram(array $organization): bool
    {
        $result = $this->commonGroundService->getResource(['component' => 'edu', 'type'=>'programs'], ['provider' => $organization['@id']])['hydra:member'];
        if (count($result) > 1) {
            return true;
        }

        return false;
    }

    /**
     * This function converts a EducationEvent to a StudentDossierEvent.
     *
     * @param array       $input        Array of data that is being converted into the StudentDossierEvent
     * @param string|null $employeeName Name of the creator that created the EducationEvent
     * @param string|null $studentId    ID of the student this StudentDossierEvent is being created for
     *
     * @throws \Exception
     *
     * @return \App\Entity\StudentDossierEvent Returns a StudentDossierEvent object
     */
    public function convertEducationEvent(array $input, ?string $employeeName = null, ?string $studentId = null): StudentDossierEvent
    {
        $studentDossierEvent = new StudentDossierEvent();
        $studentDossierEvent->setStudentDossierEventId($input['id']);
        $studentDossierEvent->setEvent($input['name']);
        $studentDossierEvent->setEventDescription($input['description']);
        $studentDossierEvent->setEventDate(new DateTime($input['startDate']));
        $studentDossierEvent->setStudentId($studentId);
        $studentDossierEvent->setCreatorGivenName($employeeName);
        $this->entityManager->persist($studentDossierEvent);
        $studentDossierEvent->setId(Uuid::fromString($input['id']));
        $this->entityManager->persist($studentDossierEvent);

        return $studentDossierEvent;
    }

    /**
     * This function fetches EducationEvents for the given person.
     *
     * @param string|null $person The person the education events are being fetched for
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection Returns a ArrayCollection holding EducationEvents
     */
    public function getEducationEvents(?string $person = null): ArrayCollection
    {
        //@TODO: This has to be knotted to the event more properly
        if ($person) {
            $results = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'education_events'], ['participants.id' => $person, 'limit' => 2000])['hydra:member'];
        } else {
            $results = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'education_events'], ['limit' => 2000])['hydra:member'];
        }
        $collection = new ArrayCollection();
        foreach ($results as $result) {
            $collection->add($this->convertEducationEvent($result, $person));
        }

        return $collection;
    }

    /**
     * This function fetches a single education event with the given ID.
     *
     * @param string $id ID of the education event that will be fetched
     *
     * @throws \Exception
     *
     * @return \App\Entity\StudentDossierEvent Returns a StudentDossierEvent
     */
    public function getEducationEvent(string $id): StudentDossierEvent
    {
        $result = $this->commonGroundService->getResource(['component' => 'edu', 'type' => 'education_events', 'id' => $id]);

        return $this->convertEducationEvent($result);
    }

    /**
     * This function creates a StudentDossierEvent with the given array and converts it to a EducationEvent.
     *
     * @param array $studentDossierEventArray Array with data for the StudentDossierEvent
     *
     * @throws \Exception
     *
     * @return \App\Entity\StudentDossierEvent Returns a EducationEvent
     */
    public function createEducationEvent(array $studentDossierEventArray): StudentDossierEvent
    {
        $employee = $this->commonGroundService->getResource(['component' => 'mrc', 'type' => 'employees', 'id' => $studentDossierEventArray['employeeId']]);
        $event = [
            'name'          => $studentDossierEventArray['event'],
            'description'   => $studentDossierEventArray['eventDescription'],
            'startDate'     => $studentDossierEventArray['eventDate'],
            'participants'  => ["/participants/{$studentDossierEventArray['studentId']}"],
            'organizer'     => $employee['@id'],
        ];
        $contact = $this->commonGroundService->getResource($employee['person']);
        $result = $this->commonGroundService->createResource($event, ['component' => 'edu', 'type' => 'education_events']);

        return $this->convertEducationEvent($result, $contact['givenName'], $studentDossierEventArray['studentId']);
    }

    /**
     * This function updates the participants of a event with the given participants.
     *
     * @param array       $event        Array with data for a event
     * @param string|null $studentId    ID of a student who is a participant of the given event
     * @param array|null  $participants Array of edu/participants
     *
     * @return array Returns the given event
     */
    public function updateParticipants(array $event, ?string $studentId = null, ?array $participants = []): array
    {
        if ($studentId) {
            $event['participants'][] = "/participants/{$studentId}";
        }
        foreach ($participants as $participant) {
            key_exists('id', $participant) ? $event['participants'][] = "/participants/{$participant['id']}" : null;
        }
        foreach ($event['participants'] as $key=>$value) {
            if (!$value) {
                unset($event['participants'][$key]);
            }
        }

        return $event;
    }

    /**
     * This function updates a StudentDossierEvent and converts it to a EducationEvent.
     *
     * @param string $id                       ID of the EducationEvent that will be updated
     * @param array  $studentDossierEventArray Array that holds data for the StudentDossierEvent
     *
     * @throws \Exception
     *
     * @return \App\Entity\StudentDossierEvent Returns a StudentDossierEvent
     */
    public function updateEducationEvent(string $id, array $studentDossierEventArray): StudentDossierEvent
    {
        $resource = $this->commonGroundService->getResource(['component' => 'edu', 'type' => 'education_events', 'id' => $id]);
        $employee = $this->commonGroundService->getResource(isset($studentDossierEventArray['employeeId']) ? ['component' => 'mrc', 'type' => 'employees', 'id' =>  $studentDossierEventArray['employeeId']] : $resource['organizer']);
        $event = [
            'name'          => key_exists('event', $studentDossierEventArray) ? $studentDossierEventArray['event'] : $resource['name'],
            'description'   => key_exists('eventDescription', $studentDossierEventArray) ? $studentDossierEventArray['eventDescription'] : $resource['description'],
            'startDate'     => key_exists('eventDate', $studentDossierEventArray) ? $studentDossierEventArray['eventDate'] : $resource['startDate'],
            'organizer'     => key_exists('employeeId', $studentDossierEventArray) ? $employee['@id'] : $resource['organizer'],
        ];
        $event = $this->updateParticipants($event, $studentDossierEventArray['studentId'] ?? null, $resource['participants']);

        $contact = $this->commonGroundService->getResource($employee['person']);
        $resource = $this->commonGroundService->updateResource($event, ['component' => 'edu', 'type' => 'education_events', 'id' => $id]);
        $studentId = count($resource['participants']) > 0 ? $resource['participants'][array_key_first($resource['participants'])]['id'] : null;

        return $this->convertEducationEvent($resource, $contact['givenName'], $studentId);
    }

    /**
     * This function deletes a edu/education_event with the given ID.
     *
     * @param string $id ID of the EducationEvent
     *
     * @return bool Returns a bool if the EducationEvent is deleted or not
     */
    public function deleteEducationEvent(string $id): bool
    {
        return $this->commonGroundService->deleteResource(null, ['component' => 'edu', 'type' => 'education_events', 'id' => $id]);
    }

    /**
     * This function fetches edu_participants with the given query.
     *
     * @param array|null $additionalQuery
     *
     * @return array Returns a array of fetched edu/participants
     */
    public function getParticipants(?array $additionalQuery = []): array
    {
        $query = array_merge($additionalQuery, ['limit' => 1000]);

        return $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], $query)['hydra:member'];
    }

    /**
     * This function sets details to a course with the given group.
     *
     * @param array $group    Array that holds group data
     * @param mixed $resource Mixed object where the groups data is added to
     *
     * @return mixed Returns a mixed object as CourseDetails
     */
    public function setGroupCourseDetails(array $group, $resource)
    {
        if (isset($group['course'])) {
            $aanbieder = explode('/', $group['course']['organization']);
            if (is_array($aanbieder)) {
                $aanbieder = end($aanbieder);
            }
            $resource->setAanbiederId($aanbieder);
            $resource->setTypeCourse($group['course']['additionalType']);
            $resource->setDetailsTotalClassHours((int) $group['course']['timeRequired']);
        } else {
            if (isset($aanbiederId)) {
                $resource->setAanbiederId($aanbiederId);
            }
            $resource->setTypeCourse(null);
            $resource->setDetailsTotalClassHours(null);
        }

        return $resource;
    }

    /**
     * THis function sets details start and end date from the given group.
     *
     * @param array $group    Array that holds group data
     * @param mixed $resource Mixed object that the data is being set for
     *
     * @throws \Exception
     *
     * @return mixed Returns a mixed object as GroupDetails
     */
    public function setGroupDetailsDates(array $group, $resource)
    {
        if (isset($group['endDate'])) {
            $resource->setDetailsEndDate(new DateTime($group['endDate']));
        }
        if (isset($group['startDate'])) {
            $resource->setDetailsStartDate(new DateTime($group['startDate']));
        }

        return $resource;
    }

    /**
     * This function converts a group array to a Group object.
     *
     * @param array $group       Array that holds the group data
     * @param null  $aanbiederId ID of the aanbieder this group belongs to
     *
     * @throws \Exception
     *
     * @return \App\Entity\Group Returns a Group object
     */
    public function convertGroupObject(array $group, $aanbiederId = null): Group
    {
        $resource = new Group();
        $resource->setAanbiederId($aanbiederId ?? $this->commonGroundService->getUuidFromUrl($group['course']['organization']));
        $resource->setGroupId($group['id']);
        $resource->setName($group['name']);
        $resource = $this->setGroupCourseDetails($group, $resource);
        $resource->setOutComesGoal($group['goal']);
        $resource->setOutComesTopic($group['topic']);
        $resource->setOutComesTopicOther($group['topicOther']);
        $resource->setOutComesApplication($group['application']);
        $resource->setOutComesApplicationOther($group['applicationOther']);
        $resource->setOutComesLevel($group['level']);
        $resource->setOutComesLevelOther($group['levelOther']);
        $resource->setDetailsIsFormal($group['isFormal']);
        $resource->setDetailsCertificateWillBeAwarded($group['certificateWillBeAwarded']);
        $resource->setGeneralLocation($group['location']);
        $resource->setGeneralParticipantsMin($group['minParticipations']);
        $resource->setGeneralParticipantsMax($group['maxParticipations']);
        $resource = $this->setGroupDetailsDates($group, $resource);
        $resource->setGeneralEvaluation($group['evaluation']);
        $resource->setAanbiederEmployeeIds($group['mentors']);

        $this->entityManager->persist($resource);
        $resource->setId(Uuid::fromString($group['id']));
        $this->entityManager->persist($resource);

        return $resource;
    }

    /**
     * This function fetches a edu/group and converts it to a Group object.
     *
     * @param string $id ID of the group that will be fetched
     *
     * @throws \Exception
     *
     * @return \App\Entity\Group Returns a Group object
     */
    public function getGroup(string $id): Group
    {
        $group = $this->eavService->getObject(['entityName' => 'groups', 'componentCode' => 'edu', 'self' => $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $id])]);

        return $this->convertGroupObject($group);
    }

    /**
     * This function fetches edu/groups and converts them into Group objects before returning them in a array.
     *
     * @param array|null $query Array with query params the fetched groups will be filtered on
     *
     * @throws \Exception
     *
     * @return array Returns an array of fetched Group objects
     */
    public function getGroups(?array $query = []): array
    {
        $groups = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'groups'], $query)['hydra:member'];

        $results = [];
        foreach ($groups as $group) {
            if ($this->eavService->hasEavObject($group['@id']) && key_exists('course', $group) && $group['course']) {
                $results[] = $this->getGroup($group['id']);
            }
        }

        return $results;
    }

    /**
     * This function fetches groups with the given status.
     *
     * @param string $aanbiederId ID of the aanbieder these groups belong to
     * @param string $status      Status that the Groups participants need to have if the Group wants to be returned
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection Returns a ArrayCollection of Groups
     */
    public function getGroupsWithStatus(string $aanbiederId, string $status): ArrayCollection
    {
        $groups = new ArrayCollection();
        // Check if provider exists in eav and get it if it does
        if ($this->eavService->hasEavObject(null, 'organizations', $aanbiederId, 'cc')) {
            //get provider
            $providerUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $aanbiederId]);
            $provider = $this->eavService->getObject(['entityName' => 'organizations', 'componentCode' => 'cc', 'self' => $providerUrl]);
            // Get the provider eav/cc/organization participations and their edu/groups urls from EAV
            $groupUrls = [];
            //$provider['participations'] contain all participations urls
            foreach ($provider['participations'] as $participationUrl) {
                try {
                    //todo: do hasEavObject checks here? For now removed because it will slow down the api call if we do to many calls in a foreach
//                    if ($this->eavService->hasEavObject($participationUrl)) {
                    // Get eav/Participation
                    $participation = $this->eavService->getObject(['entityName' => 'participations', 'self' => $participationUrl]);
                    //after isset add && hasEavObject? $this->eavService->hasEavObject($participation['learningNeed']) todo: same here?
                    //see if the status of said participation is the requested one and if the participation holds a group url
                    if ($participation['status'] == $status && isset($participation['group'])) {
                        $group = $this->checkParticipationGroup($groupUrls, $participation, $aanbiederId);
                        $group ? $groups->add($group) : null;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            // Then get the actual groups and return them...
        } else {
            // Do not throw an error, because we want to return an empty array in this case
            $result['message'] = 'Warning, '.$aanbiederId.' is not an existing eav/cc/organization!';
        }

        return $groups;
    }

    /**
     * This function checks if a participation is in a group and if not adds them.
     *
     * @param array  $groupUrls     Array of group urls
     * @param array  $participation Array with data of a participation
     * @param string $aanbiederId   ID of the aanbieder this participation belongs to
     *
     * @throws \Exception
     *
     * @return Group The resource result
     */
    public function checkParticipationGroup(array $groupUrls, array $participation, string $aanbiederId): ?Group
    {
        if (!in_array($participation['group'], $groupUrls)) {
            array_push($groupUrls, $participation['group']);
            //get group
            $group = $this->eavService->getObject(['entityName' => 'groups', 'componentCode' => 'edu', 'self' => $participation['group']]);
            //handle result
            $resourceResult = $this->convertGroupObject($group, $aanbiederId);
            $resourceResult->setId(Uuid::getFactory()->fromString($group['id']));

            return $resourceResult;
        }

        return null;
    }

    /**
     * This function deletes a group with the given id.
     *
     * @param string $id ID of the group that will be deleted
     *
     * @throws \Exception
     */
    public function deleteGroup(string $id)
    {
        $groupUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $id]);

        //check if group exists
        if (!$this->commonGroundService->isResource($groupUrl)) {
            throw new Exception('Invalid request, groupId is not an existing edu/group!');
        }
        if ($this->eavService->hasEavObject($groupUrl)) {
            $groep = $this->eavService->getObject(['entityName' => 'groups', 'componentCode' => 'edu', 'self' => $groupUrl]);
            // Remove this group from the eav/participation
            if (isset($groep['participations']) && !empty($groep['participations'])) {
                $this->removeGroupFromParticipation($groep);
            }
            //remove course
            $courseId = $groep['course']['id'];
            $courseUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'courses', 'id' => $courseId]);
            if (!$this->commonGroundService->isResource($courseUrl)) {
                throw new Exception('course could not be found!');
            }
            //delete group
            $this->eavService->deleteResource(null, ['component' => 'edu', 'type' => 'groups', 'id' => $id]);

            $course = $this->commonGroundService->getResource($courseUrl);
            $this->eavService->deleteResource(null, ['component' => 'edu', 'type' => 'courses', 'id' => $courseId]);
        }
    }

    /**
     * This function removes a group from its participations.
     *
     * @param array $group Array of data from a group
     *
     * @throws \Exception
     */
    public function removeGroupFromParticipation(array $group)
    {
        foreach ($group['participations'] as $participationUrl) {
            if ($this->eavService->hasEavObject($participationUrl)) {
                $participation = $this->eavService->getObject(['entityName' => 'participations', 'self' => $participationUrl]);
                if (isset($participation['group'])) {
                    $updateParticipation['group'] = null;
                    $updateParticipation['status'] = 'REFERRED';
                    $updateParticipation['presenceEngagements'] = null;
                    $updateParticipation['presenceStartDate'] = null;
                    $updateParticipation['presenceEndDate'] = null;
                    $updateParticipation['presenceEndParticipationReason'] = null;
                    $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participationUrl]);
                }
            }
        }
    }

    /**
     * This function changes the mentors of a group with the given employee IDs.
     *
     * @param string $groupId     ID of the group which mentors will be changed
     * @param array  $employeeids IDs of the employees that will be the groups new mentor(s)
     *
     * @throws \Exception
     *
     * @return \App\Entity\Group Returns a group object
     */
    public function changeGroupTeachers(string $groupId, array $employeeids): Group
    {
        $groupUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $groupId]);
        $groep['mentors'] = $employeeids;

        return $this->convertGroupObject($this->eavService->saveObject($groep, ['entityName' => 'groups', 'componentCode' => 'edu', 'self' => $groupUrl]));
    }

    /**
     * This function deletes the participant and its subresources of the given ID.
     *
     * @param string $id ID of the participant that will be deleted
     *
     * @throws \Exception
     *
     * @return bool Returns a bool if the participant is deleted or not
     */
    public function deleteParticipants(string $id): bool
    {
        $ccOrganization = $this->commonGroundService->getResource(['component'=>'cc', 'type' => 'organizations', 'id' => $id]);
        $program = $this->commonGroundService->getResourceList(['component' => 'edu', 'type'=>'programs'], ['provider' => $ccOrganization['@id']])['hydra:member'][0];
        $participants = $this->commonGroundService->getResourceList(['component'=>'edu', 'type' => 'participants'], ['program.id' => $program['id']])['hydra:member'];

        if ($participants > 0) {
            foreach ($participants as $participant) {
                $person = $this->commonGroundService->getResource($participant['person']);
                $this->deleteEducationEvents($participant);
                $this->deleteResults($participant);
                $this->deleteParticipantGroups($participant);
                $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'people', 'id' => $person['id']]);
                $this->eavService->deleteResource(null, ['component'=>'edu', 'type'=>'participants', 'id'=>$participant['id']]);
            }
        }

        return $program['id'];
    }

    /**
     * This function deletes the education events of the given participant.
     *
     * @param array $participant Array that holds data of the participant
     *
     * @return bool Returns false
     */
    public function deleteEducationEvents(array $participant): bool
    {
        foreach ($participant['educationEvents'] as $educationEvent) {
            $educationEvent = $this->commonGroundService->getResource($educationEvent);
            $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'education_events', 'id' => $educationEvent['id']]);
        }

        return false;
    }

    /**
     * This function deletes results of the given participant.
     *
     * @param array $participant Array that holds data of the participant
     *
     * @return bool Returns false
     */
    public function deleteResults(array $participant): bool
    {
        foreach ($participant['results'] as $result) {
            $this->eavService->deleteResource(null, ['component'=>'edu', 'type' => 'results', 'id' => $result['id']]);
        }

        return false;
    }

    /**
     * This function deletes the groups of the given participant.
     *
     * @param array $participant Array that holds data of the participant
     *
     * @throws \Exception
     *
     * @return bool Returns false
     */
    public function deleteParticipantGroups(array $participant): bool
    {
        foreach ($participant['participantGroups'] as $participantGroup) {
            $this->deleteGroup($participantGroup['id']);
        }

        return false;
    }
}
