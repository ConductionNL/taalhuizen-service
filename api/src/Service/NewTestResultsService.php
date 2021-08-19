<?php

namespace App\Service;

use App\Entity\LearningNeed;
use App\Entity\LearningNeedOutCome;
use App\Entity\Registration;
use App\Entity\TestResult;
use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class NewTestResultsService
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

    public function persistTestResult(TestResult $testResult, array $arrays): TestResult
    {
        $this->entityManager->persist($testResult);
        $testResult->setId(Uuid::fromString($arrays['testResult']['id']));
        $this->entityManager->persist($testResult);

        return $testResult;
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
        try {
            $learningNeed = $this->eavService->saveObject($learningNeed, ['entityName' => 'learning_needs', 'eavId' => $learningNeedId]);
        } catch (\Throwable $e) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'learning need');
        }

        return new ArrayCollection($learningNeed);
    }

    public function createTestResult(TestResult $testResult): TestResult
    {
        $this->checkTestResult($testResult);

        if ($this->eavService->hasEavObject(null, 'participations', $testResult->getParticipationId())) {
            $participation = $this->eavService->getObject(['entityName' => 'participations', 'eavId' => $testResult->getParticipationId()]);
        } else {
            throw new BadRequestPathException('Unable to find participation with provided id', 'participation');
        }

        $array = [
            'participation' => "/participations/" . $participation['id'],
            'memo' => $testResult->getMemo() ?? null,
            'examDate' => $testResult->getExamDate()->format('Y-m-d H:i:s'),
            'usedExam' => $testResult->getUsedExam(),
            'level' => $testResult->getLearningNeedOutCome()->getLevel(),
            'levelOther' => $testResult->getLearningNeedOutCome()->getLevelOther() ?? null,
            'application' => $testResult->getLearningNeedOutCome()->getApplication(),
            'applicationOther' => $testResult->getLearningNeedOutCome()->getApplicationOther() ?? null,
            'topic' => $testResult->getLearningNeedOutCome()->getTopic(),
            'topicOther' => $testResult->getLearningNeedOutCome()->getTopicOther() ?? null,
            'goal' => $testResult->getLearningNeedOutCome()->getGoal(),
        ];

        $arrays['testResult'] = $this->eavService->saveObject(array_filter($array), ['entityName' => 'results', 'componentCode' => 'edu']);

        return $this->persistTestResult($testResult, $arrays);
    }

    public function checkTestResult(TestResult $testResult): void
    {
        if ($testResult->getExamDate() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'exam date');
        }
        if ($testResult->getUsedExam() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'used exam');
        }
        if ($testResult->getParticipationId() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'participation id');
        }

        $this->checkLearningNeedOutcome($testResult->getLearningNeedOutCome());

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
