<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\MrcService;
use App\Service\ResolverService;

class EmployeeQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private MrcService $mrcService;
    private ResolverService $resolverService;

    /**
     * EmployeeQueryCollectionResolver constructor.
     * @param MrcService $mrcService
     * @param ResolverService $resolverService
     */
    public function __construct(MrcService $mrcService, ResolverService $resolverService)
    {
        $this->mrcService = $mrcService;
        $this->resolverService = $resolverService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $collection = $this->mrcService->getEmployees(
            key_exists('languageHouseId', $context['args']) ?
                $context['args']['languageHouseId'] :
                null,
            key_exists('providerId', $context['args']) ?
                $context['args']['providerId'] :
                null
        );

        return $this->resolverService->createPaginator($collection, $context['args']);
    }
}
