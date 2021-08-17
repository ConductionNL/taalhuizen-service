<?php

namespace App\Service;

use App\Entity\LearningNeed;
use App\Entity\LearningNeedOutCome;
use App\Entity\Registration;
use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class NewLearningNeedService
{

    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private EntityManagerInterface $entityManager;

    public function __construct(LayerService $layerService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->eavService = new EAVService($this->commonGroundService);
        $this->entityManager = $layerService->entityManager;
    }

    public function persistLearningNeed(LearningNeed $learningNeed, array $arrays): LearningNeed
    {
        $this->entityManager->persist($learningNeed);
        $learningNeed->setId(Uuid::fromString($arrays['learningNeed']['id']));
        $this->entityManager->persist($learningNeed);

        return $learningNeed;
    }

    public function deleteLearningNeed($id): Response
    {
        if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
            // Get the learningNeed from EAV
            $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'eavId' => $id]);

            // Remove this learningNeed from all EAV/edu/participants
            foreach ($learningNeed['participants'] as $studentUrl) {
                $this->removeLearningNeedFromStudent($learningNeed['@eav'], $studentUrl);
            }

            // Delete the learningNeed in EAV
            $this->eavService->deleteObject($learningNeed['eavId']);
        } else {
            throw new BadRequestPathException('Invalid request, '.$id.' is not an existing eav/learning_need!', 'learning need');
        }

        return new Response(null, 204);
    }

    public function removeLearningNeedFromStudent($learningNeedUrl, $studentUrl): array
    {
        $result = [];
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
            $participant['learningNeeds'] = array_values(array_filter($getParticipant['learningNeeds'], function ($participantLearningNeed) use ($learningNeedUrl) {
                return $participantLearningNeed != $learningNeedUrl;
            }));
            $result['participant'] = $this->eavService->saveObject($participant, ['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
        }

        return $result;
    }

    public function updateLearningNeed(array $learningNeed, string $learningNeedId): ArrayCollection
    {
        $learningNeed = $this->eavService->saveObject($learningNeed, ['entityName' => 'learning_needs', 'eavId' => $learningNeedId]);

        return new ArrayCollection($learningNeed);
    }

    public function createLearningNeed(LearningNeed $learningNeed): LearningNeed
    {
        $this->checkLearningNeed($learningNeed);

        $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $learningNeed->getStudentId()]);

        $array = [
            'description' => $learningNeed->getDescription(),
            'motivation' => $learningNeed->getMotivation(),
            'goal'              => $learningNeed->getDesiredLearningNeedOutCome()->getGoal(),
            'topic'             => $learningNeed->getDesiredLearningNeedOutCome()->getTopic(),
            'topicOther'        => $learningNeed->getDesiredLearningNeedOutCome()->getTopicOther() ?? null,
            'application'       => $learningNeed->getDesiredLearningNeedOutCome()->getApplication(),
            'applicationOther'  => $learningNeed->getDesiredLearningNeedOutCome()->getApplicationOther() ?? null,
            'level'             => $learningNeed->getDesiredLearningNeedOutCome()->getLevel(),
            'levelOther'        => $learningNeed->getDesiredLearningNeedOutCome()->getLevelOther() ?? null,
            'desiredOffer' => $learningNeed->getDesiredOffer() ?? null,
            'advisedOffer' => $learningNeed->getAdvisedOffer() ?? null,
            'offerDifference' => $learningNeed->getOfferDifference(),
            'offerDifferenceOther' => $learningNeed->getOfferDifferenceOther() ?? null,
            'offerEngagements' => $learningNeed->getOfferEngagements() ?? null,
        ];

        $arrays['learningNeed'] = $this->eavService->saveObject(array_filter($array), ['entityName' => 'learning_needs']);


        $this->addStudentToLearningNeed($studentUrl, $arrays['learningNeed']);

        return $this->persistLearningNeed($learningNeed, $arrays);
    }

    public function addStudentToLearningNeed($studentUrl, $learningNeed)
    {
        $result = [];
        // Check if student already has an EAV object
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);

            $participant['learningNeeds'] = $getParticipant['learningNeeds'] ?? [];
        } else {
            $participant['learningNeeds'] = [];
        }

        // Save the participant in EAV with the EAV/learningNeed connected to it
        if (!in_array($learningNeed['@eav'], $participant['learningNeeds'])) {
            array_push($participant['learningNeeds'], $learningNeed['@eav']);
            $participant = $this->eavService->saveObject($participant, ['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);

            // Add $participant to the $result['participant'] because this is convenient when testing or debugging (mostly for us)
            $result['participant'] = $participant;

            // Update the learningNeed to add the EAV/edu/participant to it
            if (isset($learningNeed['participants'])) {
                $updateLearningNeed['participants'] = $learningNeed['participants'];
            } else {
                $updateLearningNeed['participants'] = [];
            }
            if (!in_array($participant['@id'], $updateLearningNeed['participants'])) {
                array_push($updateLearningNeed['participants'], $participant['@id']);
                $learningNeed = $this->eavService->saveObject($updateLearningNeed, ['entityName' => 'learning_needs', 'self' => $learningNeed['@eav']]);

                // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
                $result['learningNeed'] = $learningNeed;
            }
        }

        return $result;
    }

    public function checkLearningNeed(LearningNeed $learningNeed): void
    {
        if ($learningNeed->getDescription() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'description');
        }
        if ($learningNeed->getMotivation() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'motivation');
        }
        if ($learningNeed->getDesiredLearningNeedOutCome() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'desired learning need outcome');
        }
        if ($learningNeed->getStudentId() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'student id');
        }
        if ($learningNeed->getOfferDifference() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'offer difference');
        }

        $this->checkLearningNeedOutcome($learningNeed->getDesiredLearningNeedOutCome());

    }

    public function checkLearningNeedOutcome(LearningNeedOutCome $outcome): void
    {
        if ($outcome->getGoal() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'goal');
        }
        if ($outcome->getTopic() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'topic');
        }
        if ($outcome->getApplication() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'application');
        }
        if ($outcome->getLevel() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'level');
        }

    }

}
