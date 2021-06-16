<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ParticipationService;
use App\Service\ResolverService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class ParticipationQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private ResolverService $resolverService;
    private ParticipationService $participationService;

    public function __construct(ParticipationService $participationService)
    {
        $this->participationService = $participationService;
        $this->resolverService = new ResolverService();
    }

    /**
     * Get the participations objects with the given learningNeedId.
     *
     * @inheritDoc
     *
     * @throws Exception;
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $result['result'] = [];
        if (key_exists('learningNeedId', $context['args'])) {
            $learningNeedId = explode('/', $context['args']['learningNeedId']);
            if (is_array($learningNeedId)) {
                $learningNeedId = end($learningNeedId);
            }
        } else {
            throw new Exception('The learningNeedId was not specified');
        }

        // Get the participations of this learningNeed from EAV
        $result = array_merge($result, $this->participationService->getParticipations($learningNeedId));

        $collection = new ArrayCollection();
        if (isset($result['participations'])) {
            // Now put together the expected result for Lifely:
            foreach ($result['participations'] as &$participation) {
                if (!isset($participation['errorMessage'])) {
                    $resourceResult = $this->participationService->handleResult($participation, $learningNeedId);
                    $resourceResult->setId(Uuid::getFactory()->fromString($participation['id']));
                    $collection->add($resourceResult);
                    $participation = $participation['@eav']; // Can be removed to show the entire body of all the learningNeeds when dumping $result
                }
            }
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $this->resolverService->createPaginator($collection, $context['args']);
    }
}
