<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\LearningNeedService;
use App\Service\ResolverService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class LearningNeedQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private ResolverService $resolverService;
    private LearningNeedService $learningNeedService;

    public function __construct(ResolverService $resolverService, LearningNeedService $learningNeedService)
    {
        $this->resolverService = $resolverService;
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

        if (!key_exists('studentId', $context['args'])) {
            throw new Exception('The studentId was not specified');
        }
        $studentId = $this->handleStudentId($context);
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

        return $this->resolverService->createPaginator($collection, $context['args']);
    }

    public function handleStudentId($context)
    {
        $studentId = explode('/', $context['args']['studentId']);
        if (is_array($studentId)) {
            $studentId = end($studentId);
        }

        return $studentId;
    }
}
