<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\TestResultService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ramsey\Uuid\Uuid;

class TestResultQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private TestResultService $testResultService;

    public function __construct(TestResultService $testResultService){
        $this->testResultService = $testResultService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if(key_exists('participationId', $context['args'])){
            $participationId = explode('/',$context['args']['participationId']);
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

        return $this->createPaginator($collection, $context['args']);
    }

    public function createPaginator(ArrayCollection $collection, array $args){
        if(key_exists('first', $args)){
            $maxItems = $args['first'];
            $firstItem = 0;
        } elseif(key_exists('last', $args)) {
            $maxItems = $args['last'];
            $firstItem = (count($collection) - 1) - $maxItems;
        } else {
            $maxItems = count($collection);
            $firstItem = 0;
        }
        if(key_exists('after', $args)){
            $firstItem = base64_decode($args['after']);
        } elseif(key_exists('before', $args)){
            $firstItem = base64_decode($args['before']) - $maxItems;
        }
        return new ArrayPaginator($collection->toArray(), $firstItem, $maxItems);
    }
}
