<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ResolverService;
use Doctrine\Common\Collections\ArrayCollection;

class UserQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private ResolverService $resolverService;

    /**
     * UserQueryCollectionResolver constructor.
     *
     * @param ResolverService $resolverService
     */
    public function __construct(ResolverService $resolverService)
    {
        $this->resolverService = $resolverService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $collection = new ArrayCollection();

        //@TODO implement logic to find stuff and put in the iterator
        return $this->resolverService->createPaginator($collection, $context['args']);
    }
}
