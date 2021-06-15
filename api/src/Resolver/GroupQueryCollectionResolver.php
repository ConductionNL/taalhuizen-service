<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\EDUService;
use App\Service\ResolverService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

class GroupQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private EDUService $eduService;
    private ResolverService $resolverService;

    public function __construct(EDUService $eduService, ResolverService $resolverService)
    {
        $this->eduService = $eduService;
        $this->resolverService = $resolverService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (key_exists('aanbiederId', $context['args'])) {
            $aanbiederId = explode('/', $context['args']['aanbiederId']);
            if (is_array($aanbiederId)) {
                $aanbiederId = end($aanbiederId);
            }
        } else {
            $aanbiederId = null;
        }

        switch ($context['info']->operation->name->value) {
            case 'activeGroups':
                return $this->resolverService->createPaginator($this->eduService->getGroupsWithStatus($aanbiederId, 'ACTIVE'), $context['args']);
            case 'futureGroups':
                return $this->resolverService->createPaginator($this->futureGroups(['course.organization' => $aanbiederId]), $context['args']);
            case 'completedGroups':
                return $this->resolverService->createPaginator($this->eduService->getGroupsWithStatus($aanbiederId, 'COMPLETED'), $context['args']);
            default:
                return $this->resolverService->createPaginator($this->getGroups(['course.organization' => $aanbiederId]), $context['args']);
        }
    }

    /**
     * Gets the groups with the given query as filter, if any query params are given. Uses the eduService->getGroups function.
     *
     * @param array|null $query the query params to filter groups with.
     *
     * @return ArrayCollection the collection of groups.
     */
    public function getGroups(?array $query = []): ArrayCollection
    {
        return new ArrayCollection($this->eduService->getGroups($query));
    }

    /**
     * Gets the groups that have a startDate in the future. If any query params are given they will also be used to filter with.
     *
     * @param array|null $query the query params to filter groups with.
     *
     * @return ArrayCollection the collection of groups.
     */
    public function futureGroups(?array $query = []): ArrayCollection
    {
        $now = new DateTime('now');
        $now = $now->format('Ymd');
        $query = array_merge($query, ['startDate[strictly_after]' => $now]);

        return $this->getGroups($query);
    }
}
