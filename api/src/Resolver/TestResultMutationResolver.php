<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\TestResult;
use App\Service\TestResultService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class TestResultMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private TestResultService $testResultService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->testResultService = new TestResultService($commonGroundService);
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof TestResult && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createTestResult':
                return $this->createTestResult($context['info']->variableValues['input']);
            case 'updateTestResult':
                return $this->updateTestResult($context['info']->variableValues['input']);
            case 'removeTestResult':
                return $this->removeTestResult($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createTestResult(array $input): TestResult
    {
        if (isset($input['participationId'])) {
            $participationId = explode('/', $input['participationId']);
            if (is_array($participationId)) {
                $participationId = end($participationId);
            }
        } else {
            throw new Exception('Invalid request, participationId is not set!');
        }

        // Transform input info to testResult and memo body...
        $testResult = $this->inputToTestResult($input);
        $memo = $this->inputToResultMemo($input);

        // Do some checks and error handling
        $testResult = $this->testResultService->checkTestResultValues($testResult, $participationId);

        // Save TestResult and its memo and connect eav/participation to TestResult
        $testResult = $this->testResultService->saveTestResult($testResult, $memo, $participationId);

        // Now put together the expected result for Lifely:
        $resourceResult = $this->testResultService->handleResult($testResult['testResult'], $testResult['memo'], $participationId);
        $resourceResult->setId(Uuid::getFactory()->fromString($testResult['testResult']['id']));

        $this->entityManager->persist($resourceResult);

        return $resourceResult;
    }

    public function updateTestResult(array $input): TestResult
    {
        $testResultId = explode('/', $input['id']);
        if (is_array($testResultId)) {
            $testResultId = end($testResultId);
        }
        $testResultUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'results', 'id' => $testResultId]);
        // Get the memo for this result
        $memos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['topic'=>$testResultUrl])['hydra:member'];
        $memo = [];
        if (count($memos) > 0) {
            $memo = $memos[0];
        }

        // Transform input info to testResult and memo body...
        $testResult = $this->inputToTestResult($input);
        $memo = array_merge($memo, $this->inputToResultMemo($input));

        // Do some checks and error handling
        $testResult = $this->testResultService->checkTestResultValues($testResult, null, $testResultUrl);

        // Save TestResult and its memo and connect eav/participation to TestResult
        $testResult = $this->testResultService->saveTestResult($testResult, $memo, null, $testResultUrl);

        // Now put together the expected result for Lifely:
        $resourceResult = $this->testResultService->handleResult($testResult['testResult'], $testResult['memo']);
        $resourceResult->setId(Uuid::getFactory()->fromString($testResult['testResult']['id']));

        $this->entityManager->persist($resourceResult);

        return $resourceResult;
    }

    public function removeTestResult(array $testResult): ?TestResult
    {
        if (isset($testResult['id'])) {
            $testResultId = explode('/', $testResult['id']);
            if (is_array($testResultId)) {
                $testResultId = end($testResultId);
            }
        } else {
            throw new Exception('No id was specified!');
        }

        $this->testResultService->deleteTestResult($testResultId);

        return null;
    }

    private function inputToTestResult(array $input)
    {
        // Get all info from the input array for creating/updating a TestResult and return the body for this
        return [
            'name'             => $input['examUsedExam'],
            'completionDate'   => $input['examDate'],
            'goal'             => $input['outComesGoal'],
            'topic'            => $input['outComesTopic'],
            'topicOther'       => $input['outComesTopicOther'] ?? null,
            'application'      => $input['outComesApplication'],
            'applicationOther' => $input['outComesApplicationOther'] ?? null,
            'level'            => $input['outComesLevel'],
            'levelOther'       => $input['outComesLevelOther'] ?? null,
        ];
    }

    private function inputToResultMemo(array $input)
    {
        // Get all info from the input array for creating/updating a memo and return the body for this
        return [
            'name'        => 'Memo for result '.$input['examUsedExam'],
            'description' => $input['examMemo'] ?? null,
        ];
    }
}
