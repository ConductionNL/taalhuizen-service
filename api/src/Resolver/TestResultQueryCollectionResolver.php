<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ResolverService;
use App\Service\TestResultService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Util\Test;
use Ramsey\Uuid\Uuid;

class TestResultQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private TestResultService $testResultService;
    private ResolverService $resolverService;
    private CommonGroundService $commonGroundService;

    public function __construct
    (
        ResolverService $resolverService,
        CommonGroundService $commonGroundService
    ) {
        $this->testResultService = new TestResultService($commonGroundService);
        $this->resolverService = $resolverService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (key_exists('participationId', $context['args'])) {
            $participationId = explode('/', $context['args']['participationId']);
            if (is_array($participationId)) {
                $participationId = end($participationId);
            }
        } else {
            throw new Exception('The participationId was not specified');
        }

        $testResults = $this->testResultService->getTestResults($participationId);

        $collection = new ArrayCollection();
        // Now put together the expected result for Lifely:
        foreach ($testResults as $testResult) {
            if (isset($testResult['testResult']['id'])) {
                $resourceResult = $this->testResultService->handleResult($testResult['testResult'], $testResult['memo']);
                $resourceResult->setId(Uuid::getFactory()->fromString($testResult['testResult']['id']));
                $collection->add($resourceResult);
            }
        }

        return $this->resolverService->createPaginator($collection, $context['args']);
    }
}
