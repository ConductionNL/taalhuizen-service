<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\CCService;
use App\Service\ResolverService;
use App\Service\UcService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;

class ProviderQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CCService $ccService;
    private UcService $ucService;
    private ResolverService $resolverService;

    public function __construct(
        CCService $ccService,
        UcService $ucService,
        ResolverService $resolverService
    ) {
        $this->ccService = $ccService;
        $this->ucService = $ucService;
        $this->resolverService = $resolverService;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception;
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        switch ($context['info']->operation->name->value) {
            case 'providers':
                $collection = $this->ccService->getOrganizations($type = 'Aanbieder');

                return $this->resolverService->createPaginator($collection, $context['args']);
            case 'userRolesByProviders':
                $collection = $this->ucService->getUserRolesByOrganization(
                    key_exists('providerId', $context['args']) ?
                        $context['args']['providerId'] :
                        null,
                    'Aanbieder'
                );

                return $this->resolverService->createPaginator($collection, $context['args']);

            default:
                return $this->resolverService->createPaginator(new ArrayCollection(), $context['args']);
        }
    }
}
