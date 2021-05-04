<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\TestResult;
use App\Service\TestResultService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class TestResultMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private TestResultService $testResultService;

    public function __construct(EntityManagerInterface $entityManager, TestResultService $testResultService){
        $this->entityManager = $entityManager;
        $this->testResultService = $testResultService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof TestResult && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
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
            $participationId = explode('/',$input['participationId']);
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
        $id = explode('/',$input['id']);
        $testResult = new TestResult();


        $this->entityManager->persist($testResult);
        return $testResult;
    }

    public function removeTestResult(array $testResult): ?TestResult
    {

        return null;
    }

    private function inputToTestResult(array $input) {
        // Get all info from the input array for creating/updating a TestResult and return the body for this
        return [
            'name' => $input['examUsedExam'],
            'completionDate' => $input['examDate'],
            'goal' => $input['outComesGoal'],
            'topic' => $input['outComesTopic'],
            'topicOther' => $input['outComesTopicOther'] ?? null,
            'application' => $input['outComesApplication'],
            'applicationOther' => $input['outComesApplicationOther'] ?? null,
            'level' => $input['outComesLevel'],
            'levelOther' => $input['outComesLevelOther'] ?? null,
        ];
    }

    private function inputToResultMemo(array $input) {
        // Get all info from the input array for creating/updating a memo and return the body for this
        return [
            'name' => 'Memo for result '.$input['examUsedExam'],
            'description' => $input['examMemo'] ?? null,
        ];
    }
}
