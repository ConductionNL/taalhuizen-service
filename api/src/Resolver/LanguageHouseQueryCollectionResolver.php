<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\CCService;
use App\Service\ResolverService;
use App\Service\UcService;
use Doctrine\Common\Collections\ArrayCollection;

class LanguageHouseQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CCService $ccService;
    private UcService $ucService;
    private ResolverService $resolverService;

    /**
     * LanguageHouseQueryCollectionResolver constructor.
     *
     * @param CCService $ccService
     * @param UcService $ucService
     */
    public function __construct(
        CCService $ccService,
        UcService $ucService
    ) {
        $this->ccService = $ccService;
        $this->ucService = $ucService;
        $this->resolverService = new ResolverService();
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        switch ($context['info']->operation->name->value) {
            case 'languageHouses':
                $collection = $this->ccService->getOrganizations($type = 'Taalhuis');

                return $this->resolverService->createPaginator($collection, $context['args']);
            case 'userRolesByLanguageHouses':
                $collection = $this->ucService->getUserRolesByOrganization(
                    key_exists('languageHouseId', $context['args']) ?
                        $context['args']['languageHouseId'] :
                        null,
                    'Taalhuis'
                );

                return $this->resolverService->createPaginator($collection, $context['args']);
            default:
                return $this->resolverService->createPaginator(new ArrayCollection(), $context['args']);
        }
    }
}
