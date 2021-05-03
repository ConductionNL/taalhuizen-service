<?php


namespace App\Service;

use App\Entity\Group;
use App\Entity\StudentDossierEvent;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Promise\Each;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
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

    public function saveEavParticipant($body, $participantUrl = null) {
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

    //@todo uitwerken
    public function saveProgram($organization){
        $program = [];
        $program['name'] = $organization['name'];
        $program['provider'] = $organization['@id'];

        $program = $this->commonGroundService->saveResource($program,['component' => 'edu','type'=>'programs']);
        return $program;
    }

    public function getProgram($organization){
        return $result = $this->commonGroundService->getResource(['component' => 'edu','type'=>'programs'], ['provider' => $organization['@id']])['hydra:member'];
    }

    public function hasProgram($organization){
        $result = $this->commonGroundService->getResource(['component' => 'edu','type'=>'programs'], ['provider' => $organization['@id']])['hydra:member'];
        if (count($result) > 1){
            return true;
        }
        return false;
    }

    public function convertEducationEvent(array $input, ?string $studentId = null): StudentDossierEvent
    {
        $studentDossierEvent = new StudentDossierEvent();
        $studentDossierEvent->setStudentDossierEventId($input['id']);
        $studentDossierEvent->setEvent($input['name']);
        $studentDossierEvent->setEventDescription($input['description']);
        $studentDossierEvent->setEventDate(new DateTime($input['startDate']));
        $studentDossierEvent->setStudentId($studentId);
        $this->entityManager->persist($studentDossierEvent);
        $studentDossierEvent->setId(Uuid::fromString($input['id']));
        $this->entityManager->persist($studentDossierEvent);

        return $studentDossierEvent;
    }

    public function getEducationEvents(?string $person = null): ArrayCollection
    {
        //@TODO: This has to be knotted to the event more properly
        if($person){
            $results = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'education_events'], ['participants.id' => $person, 'limit' => 2000])['hydra:member'];
        } else {
            $results = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'education_events'], ['limit' => 2000])['hydra:member'];
        }
        $collection = new ArrayCollection();
        foreach($results as $result){
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
        $event = [
            'name'          => $studentDossierEventArray['event'],
            'description'   => $studentDossierEventArray['eventDescription'],
            'startDate'     => $studentDossierEventArray['eventDate'],
            'participants' => ["/participants/{$studentDossierEventArray['studentId']}"]

        ];
        $result = $this->commonGroundService->createResource($event, ['component' => 'edu', 'type' => 'education_events']);

        return $this->convertEducationEvent($result, $studentDossierEventArray['studentId']);
    }

    public function updateEducationEvent(string $id, array $studentDossierEventArray): StudentDossierEvent
    {
        $event = [
            'name'          => key_exists('event', $studentDossierEventArray) ? $studentDossierEventArray['event'] : null,
            'description'   => key_exists('eventDescription', $studentDossierEventArray) ? $studentDossierEventArray['eventDescription'] : null,
            'startDate'     => key_exists('eventDate', $studentDossierEventArray) ? $studentDossierEventArray['eventDate'] : null,
            'participants'  => key_exists('studentId', $studentDossierEventArray) ? ["/participants/{$studentDossierEventArray['studentId']}"] : null,
        ];
        $result = $this->commonGroundService->updateResource($event, ['component' => 'edu', 'type' => 'education_events', 'id' => $id]);
        $studentId = count($result['participants']) > 0 ? $result['participants'][array_key_first($result['participants'])]['id'] : null;
        return $this->convertEducationEvent($result, $studentId);
    }

    public function deleteEducationEvent(string $id): bool
    {
        return $this->commonGroundService->deleteResource(null, ['component' => 'edu', 'type' => 'education_events', 'id' => $id]);
    }

    public function getParticipants(?string $languageHouse, ?DateTime $dateFrom, ?DateTime $dateUntil): array
    {
        return $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], ['extend' => 'person'])['hydra:member'];
    }

    public function convertGroupObject(array $group): Group
    {
        $aanbieder = explode('/',$group['course']['organization']);
        $resource = new Group();
        $resource->setGroupId($group['id']);
        $resource->setAanbiederId(end($aanbieder));
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

    public function getGroup(string $id): Group
    {
        $group = $this->eavService->getObject('groups',$this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $id]), 'edu');

        $result = $this->convertGroupObject($group);

        return $result;
    }

    public function getGroups(?array $query = []): array
    {
        $groups = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'groups'], $query)['hydra:member'];

        $results = [];
        foreach($groups as $group){
            if($this->eavService->hasEavObject($group['@id']) && key_exists('course', $group) && $group['course']){
                $results[] = $this->getGroup($group['id']);
            }
        }
        return $results;
    }

    public function getGroupsWithStatus($aanbiederId, $status): ArrayCollection{
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
                        if(!in_array($participation['group'],$groupUrls)){

                            array_push($groupUrls, $participation['group']);
                            //get group
                            $group = $this->eavService->getObject('groups',$participation['group'],'edu');
                            //handle result
                            $resourceResult = $this->convertGroupObject($group);
                            $resourceResult->setId(Uuid::getFactory()->fromString($group['id']));
                            $watanders->add($resourceResult);
                        }

                    }
                    //else {
                      //  $result['message'] = 'Warning, '. $participation['learningNeed'] .' is not an existing eav/learning_need!';
                    //}
                    //                } else {
                    //                    $result['message'] = 'Warning, '. $participationUrl .' is not an existing eav/participation!';
                    //                }
                } catch (Exception $e) {
                    continue;
                }
            }
            // Then get the actual groups and return them...
        } else {
            // Do not throw an error, because we want to return an empty array in this case
            $result['message'] = 'Warning, '. $aanbiederId .' is not an existing eav/cc/organization!';
        }
        return $watanders;
    }
}
