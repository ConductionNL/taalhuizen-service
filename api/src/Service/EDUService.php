<?php


namespace App\Service;

use App\Entity\StudentDossierEvent;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EDUService
{
    private EntityManagerInterface $em;
    private CommonGroundService $commonGroundService;
    private ParameterBagInterface $params;

    public function __construct(EntityManagerInterface $em, CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
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
        $this->em->persist($studentDossierEvent);
        $studentDossierEvent->setId(Uuid::fromString($input['id']));
        $this->em->persist($studentDossierEvent);

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
}
