<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\TestResultService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;
use Ramsey\Uuid\Uuid;

class TestResultQueryItemResolver implements QueryItemResolverInterface
{
    private CommonGroundService $commonGroundService;
    private TestResultService $testResultService;

    public function __construct(
        CommongroundService $commonGroundService
    ) {
        $this->commonGroundService = $commonGroundService;
        $this->testResultService = new TestResultService($commonGroundService);
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function __invoke($item, array $context)
    {
        if (key_exists('testResultId', $context['info']->variableValues)) {
            $testResultId = $context['info']->variableValues['testResultId'];
        } elseif (key_exists('id', $context['args'])) {
            $testResultId = $context['args']['id'];
        } else {
            throw new Exception('The testResultId was not specified');
        }
        $testResultId = explode('/', $testResultId);
        if (is_array($testResultId)) {
            $testResultId = end($testResultId);
        }

        $testResult = $this->testResultService->getTestResult($testResultId);

        if (isset($testResult['testResult']['id'])) {
            $resourceResult = $this->testResultService->handleResult($testResult['testResult'], $testResult['memo']);
            $resourceResult->setId(Uuid::getFactory()->fromString($testResult['testResult']['id']));
        } else {
            throw new Exception('No testResult id was found!');
        }

        return $resourceResult;
    }
}
