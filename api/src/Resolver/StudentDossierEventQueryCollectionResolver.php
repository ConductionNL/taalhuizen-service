<?php

namespace App\Resolver;

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\EDUService;
use App\Service\ResolverService;
use Doctrine\Common\Collections\ArrayCollection;

class StudentDossierEventQueryCollectionResolver implements QueryCollectionResolverInterface
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
        if (key_exists('studentId', $context['args'])) {
            $collection = $this->eduService->getEducationEvents($context['args']['studentId']);
        } else {
            $collection = $this->eduService->getEducationEvents();
        }

        return $this->resolverService->createPaginator($collection, $context['args']);
    }


}
