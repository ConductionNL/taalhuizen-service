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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EDUService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private ParameterBagInterface $params;
    private EAVService $eavService;

    public function __construct(EntityManagerInterface $em, CommonGroundService $commonGroundService, ParameterBagInterface $params, EAVService $eavService)
    {
        $this->entityManager = $em;
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
        $this->eavService = $eavService;
    }

    public function saveEavParticipant($body, $participantUrl = null)
    {
        // Save the edu/participant in EAV
        if (isset($participantUrl)) {
            // Update
            $person = $this->eavService->saveObject($body, 'participants', 'edu', $participantUrl);
        } else {
            // Create
            $person = $this->eavService->saveObject($body, 'participants', 'edu');
        }

        return $person;
    }

    public function saveEavResult($body, $resultUrl = null)
    {
        // Save the edu/result in EAV
        if (isset($resultUrl)) {
            // Update
            $result = $this->eavService->saveObject($body, 'results', 'edu', $resultUrl);
        } else {
            // Create
            $result = $this->eavService->saveObject($body, 'results', 'edu');
        }

        return $result;
    }

    //@todo uitwerken
    public function saveProgram($organization, $update = false)
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

    public function getProgram($organization)
    {
        return $result = $this->commonGroundService->getResourceList(['component' => 'edu', 'type'=>'programs'], ['provider' => $organization['@id']])['hydra:member'][0];
    }

    public function hasProgram($organization)
    {
        $result = $this->commonGroundService->getResource(['component' => 'edu', 'type'=>'programs'], ['provider' => $organization['@id']])['hydra:member'];
        if (count($result) > 1) {
            return true;
        }

        return false;
    }

    public function convertEducationEvent(array $input, string $employeeName = null, ?string $studentId = null): StudentDossierEvent
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

    public function getEducationEvent(string $id): StudentDossierEvent
    {
        $result = $this->commonGroundService->getResource(['component' => 'edu', 'type' => 'education_events', 'id' => $id]);

        return $this->convertEducationEvent($result);
    }

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

    public function deleteEducationEvent(string $id): bool
    {
        return $this->commonGroundService->deleteResource(null, ['component' => 'edu', 'type' => 'education_events', 'id' => $id]);
    }

    public function getParticipants(?array $additionalQuery = []): array
    {
        $query = array_merge($additionalQuery, ['limit' => 1000]);

        return $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], $query)['hydra:member'];
    }

    public function setGroupCourseDetails($group, $resource)
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

    public function setGroupDetailsDates($group, $resource)
    {
        if (isset($group['endDate'])) {
            $resource->setDetailsEndDate(new DateTime($group['endDate']));
        }
        if (isset($group['startDate'])) {
            $resource->setDetailsStartDate(new DateTime($group['startDate']));
        }

        return $resource;
    }

    public function convertGroupObject(array $group, $aanbiederId = null): Group
    {
        $resource = new Group();
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

    public function getGroup(string $id): Group
    {
        $group = $this->eavService->getObject('groups', $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $id]), 'edu');

        $result = $this->convertGroupObject($group);

        return $result;
    }

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

    public function getGroupsWithStatus($aanbiederId, $status): ArrayCollection
    {
        $watanders = new ArrayCollection();
        // Check if provider exists in eav and get it if it does
        if ($this->eavService->hasEavObject(null, 'organizations', $aanbiederId, 'cc')) {
            //get provider
            $providerUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $aanbiederId]);
            $provider = $this->eavService->getObject('organizations', $providerUrl, 'cc');
            // Get the provider eav/cc/organization participations and their edu/groups urls from EAV
            $groupUrls = [];
            //$provider['participations'] contain all participations urls
            foreach ($provider['participations'] as $participationUrl) {
                try {
                    //todo: do hasEavObject checks here? For now removed because it will slow down the api call if we do to many calls in a foreach
//                    if ($this->eavService->hasEavObject($participationUrl)) {
                    // Get eav/Participation
                    $participation = $this->eavService->getObject('participations', $participationUrl);
                    //after isset add && hasEavObject? $this->eavService->hasEavObject($participation['learningNeed']) todo: same here?
                    //see if the status of said participation is the requested one and if the participation holds a group url
                    if ($participation['status'] == $status && isset($participation['group'])) {
                        if (!in_array($participation['group'], $groupUrls)) {
                            array_push($groupUrls, $participation['group']);
                            //get group
                            $group = $this->eavService->getObject('groups', $participation['group'], 'edu');
                            //handle result
                            $resourceResult = $this->convertGroupObject($group, $aanbiederId);
                            $resourceResult->setId(Uuid::getFactory()->fromString($group['id']));
                            $watanders->add($resourceResult);
                        }
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

        return $watanders;
    }

    public function deleteGroup($id)
    {
        $groupUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $id]);

        //check if group exists
        if (!$this->commonGroundService->isResource($groupUrl)) {
            throw new Exception('Invalid request, groupId is not an existing edu/group!');
        }
        if ($this->eavService->hasEavObject($groupUrl)) {
            $groep = $this->eavService->getObject('groups', $groupUrl, 'edu');
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

    public function removeGroupFromParticipation($group)
    {
        foreach ($group['participations'] as $participationUrl) {
            if ($this->eavService->hasEavObject($participationUrl)) {
                $participation = $this->eavService->getObject('participations', $participationUrl);
                if (isset($participation['group'])) {
                    $updateParticipation['group'] = null;
                    $updateParticipation['status'] = 'REFERRED';
                    $updateParticipation['presenceEngagements'] = null;
                    $updateParticipation['presenceStartDate'] = null;
                    $updateParticipation['presenceEndDate'] = null;
                    $updateParticipation['presenceEndParticipationReason'] = null;
                    $participation = $this->eavService->saveObject($updateParticipation, 'participations', 'eav', $participationUrl);
                }
            }
        }
    }

    public function changeGroupTeachers($groupId, $employeeids): Group
    {
        $groupUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $groupId]);
        $groep['mentors'] = $employeeids;

        return $this->convertGroupObject($this->eavService->saveObject($groep, 'groups', 'edu', $groupUrl));
    }

    public function deleteParticipants($id): bool
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

    public function deleteEducationEvents($participant): bool
    {
        $educationEvents = $this->commonGroundService->getResource($participant['educationEvents']);
        foreach ($educationEvents as $educationEvent) {
            $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'education_events', 'id' => $educationEvent['id']]);
        }

        return false;
    }

    public function deleteResults($participant): bool
    {
        $results = $this->commonGroundService->getResource($participant['results']);
        foreach ($results as $result) {
            $this->commonGroundService->deleteResource(null, ['component'=>'edu', 'type' => 'results', 'id' => $result['id']]);
        }

        return false;
    }

    public function deleteParticipantGroups($participant): bool
    {
        $participantGroups = $this->commonGroundService->getResource($participant['participantGroups']);
        foreach ($participantGroups as $participantGroup) {
            $this->deleteGroup($participantGroup['id']);
        }

        return false;
    }
}
