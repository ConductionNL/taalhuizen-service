<?php

namespace App\Resolver;

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ParticipationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class ParticipationQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CommonGroundService $commonGroundService;
    private ParticipationService $participationService;

    public function __construct(CommongroundService $commonGroundService, ParticipationService $participationService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->participationService = $participationService;
    }

    /**
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
                    $participation = $participation['@id']; // Can be removed to show the entire body of all the learningNeeds when dumping $result
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
