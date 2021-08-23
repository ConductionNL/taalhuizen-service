<?php

namespace App\Service;

use App\Entity\LearningNeedOutCome;
use App\Entity\TestResult;
use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class NewTestResultsService
{
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private EntityManagerInterface $entityManager;
    private NewLearningNeedService $learningNeedService;

    public function __construct(LayerService $layerService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->eavService = new EAVService($this->commonGroundService);
        $this->entityManager = $layerService->entityManager;
        $this->learningNeedService = new NewLearningNeedService($layerService);
    }

    public function persistTestResult(TestResult $testResult, array $arrays): TestResult
    {
        $this->entityManager->persist($testResult);
        $testResult->setId(Uuid::fromString($arrays['testResult']['id']));
        $this->entityManager->persist($testResult);

        return $testResult;
    }

    public function deleteTestResult($id): Response
    {
        try {
            $resultUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'results', 'id' => $id]);
            $result = $this->eavService->getObject(['entityName' => 'results', 'componentCode' => 'edu', 'self' => $resultUrl]);

            $this->removeTestResultFromParticipation($result);

            $this->eavService->deleteResource(null, ['component'=>'edu', 'type'=>'results', 'id'=>$id]);
        } catch (\Throwable $e) {
            throw new BadRequestPathException('Invalid request, '.$id.' is not an existing eav/result!', 'test result');
        }

        return new Response(null, 204);
    }

    public function removeTestResultFromParticipation(array $testResult)
    {
        if ($this->eavService->hasEavObject($testResult['participation'])) {
            $participation = $this->eavService->getObject(['entityName' => 'participations', 'self' => $testResult['participation']]);
            $updateParticipation['results'] = array_values(array_filter($participation['results'], function ($participationTestResult) use ($testResult) {
                return $participationTestResult != $testResult['@eav'];
            }));
            $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'eavId' => $participation['id']]);
        }
    }

    public function updateTestResult(array $testResult, string $testResultId): ArrayCollection
    {
        $result = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'results', 'id' => $testResultId]);

        try {
            $testResult = $this->eavService->saveObject($testResult, ['entityName' => 'results', 'componentCode' => 'edu', 'self' => $result]);
        } catch (\Throwable $e) {
            throw new BadRequestPathException('Invalid value provided', 'test result');
        }

        return new ArrayCollection($testResult);
    }

    public function createTestResult(TestResult $testResult): TestResult
    {
        $this->checkTestResult($testResult);

        if ($this->eavService->hasEavObject(null, 'participations', $testResult->getParticipationId())) {
            $participation = $this->eavService->getObject(['entityName' => 'participations', 'eavId' => $testResult->getParticipationId()]);
        } else {
            throw new BadRequestPathException('Invalid request, '.$testResult->getParticipationId().' is not an existing eav/participation!', 'participation');
        }

        $this->checkParticipationType($participation);

        $array = [
            'participation'    => $participation['@eav'],
            'memo'             => $testResult->getMemo() ?? null,
            'examDate'         => $testResult->getExamDate()->format('Y-m-d H:i:s'),
            'usedExam'         => $testResult->getUsedExam(),
            'level'            => $testResult->getLearningNeedOutCome()->getLevel(),
            'levelOther'       => $testResult->getLearningNeedOutCome()->getLevelOther() ?? null,
            'application'      => $testResult->getLearningNeedOutCome()->getApplication(),
            'applicationOther' => $testResult->getLearningNeedOutCome()->getApplicationOther() ?? null,
            'topic'            => $testResult->getLearningNeedOutCome()->getTopic(),
            'topicOther'       => $testResult->getLearningNeedOutCome()->getTopicOther() ?? null,
            'goal'             => $testResult->getLearningNeedOutCome()->getGoal(),
        ];

        $arrays['testResult'] = $this->eavService->saveObject(array_filter($array), ['entityName' => 'results', 'componentCode' => 'edu']);
        $this->addResultToParticipation($arrays['testResult'], $participation);

        return $this->persistTestResult($testResult, $arrays);
    }

    public function checkParticipationType(array $participation): void
    {
        if ($participation['aanbiederId'] != null && $participation['aanbiederNote'] != null && $participation['aanbiederName'] == null) {
            throw new BadRequestPathException('Creating test results is only possible with a provider of the type "Custom verwijzing"', 'provider');
        }
    }

    public function addResultToParticipation($result, $participation): void
    {
        if (isset($participation['results'])) {
            $updateParticipation['results'] = $participation['results'];
        } else {
            $updateParticipation['results'] = [];
        }

        if (!in_array($result['@eav'], $updateParticipation['results'])) {
            array_push($updateParticipation['results'], $result['@eav']);
            $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'eavId' => $participation['id']]);
        }
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

        $this->learningNeedService->checkLearningNeedOutcome($testResult->getLearningNeedOutCome());
    }

}
