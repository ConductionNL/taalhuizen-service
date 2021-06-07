<?php

namespace App\Resolver;

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\LearningNeedService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class LearningNeedQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CommonGroundService $commonGroundService;
    private LearningNeedService $learningNeedService;

    public function __construct(CommongroundService $commonGroundService, LearningNeedService $learningNeedService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->learningNeedService = $learningNeedService;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception;
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $result['result'] = [];
        if (key_exists('studentId', $context['args'])) {
            $studentId = explode('/', $context['args']['studentId']);
            if (is_array($studentId)) {
                $studentId = end($studentId);
            }
        } else {
            throw new Exception('The studentId was not specified');
        }

        // Get the learningNeeds of this student from EAV
        $result = array_merge($result, $this->learningNeedService->getLearningNeeds($studentId));

        $collection = new ArrayCollection();
        if (isset($result['learningNeeds'])) {
            // Now put together the expected result for Lifely:
            foreach ($result['learningNeeds'] as &$learningNeed) {
                if (!isset($learningNeed['errorMessage'])) {
                    $resourceResult = $this->learningNeedService->handleResult($learningNeed, $studentId);
                    $resourceResult->setId(Uuid::getFactory()->fromString($learningNeed['id']));
                    $collection->add($resourceResult);
                    $learningNeed = $learningNeed['@id']; // Can be removed to show the entire body of all the learningNeeds when dumping $result
                }
            }
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $this->createPaginator($collection, $context['args']);
    }

    public function createPaginator(ArrayCollection $collection, array $args)
    {
        if (key_exists('first', $args)) {
            $maxItems = $args['first'];
            $firstItem = 0;
        } elseif (key_exists('last', $args)) {
            $maxItems = $args['last'];
            $firstItem = (count($collection) - 1) - $maxItems;
        } else {
            $maxItems = count($collection);
            $firstItem = 0;
        }
        if (key_exists('after', $args)) {
            $firstItem = base64_decode($args['after']);
        } elseif (key_exists('before', $args)) {
            $firstItem = base64_decode($args['before']) - $maxItems;
        }

        return new ArrayPaginator($collection->toArray(), $firstItem, $maxItems);
    }
}
