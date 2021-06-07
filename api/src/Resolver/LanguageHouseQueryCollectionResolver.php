<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\LanguageHouseService;
use App\Service\ResolverService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class LanguageHouseQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private LanguageHouseService $languageHouseService;
    private ResolverService $resolverService;

    public function __construct(LanguageHouseService $languageHouseService, ResolverService $resolverService)
    {
        $this->languageHouseService = $languageHouseService;
        $this->resolverService = $resolverService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        switch ($context['info']->operation->name->value) {
            case 'languageHouses':
                return $this->resolverService->createPaginator($this->languageHouses($context), $context['args']);
            case 'userRolesByLanguageHouses':
                return $this->resolverService->createPaginator($this->userRolesByLanguageHouses($context), $context['args']);
            default:
                return $this->resolverService->createPaginator(new ArrayCollection(), $context['args']);
        }
    }

    public function languageHouses(?array $context): ?ArrayCollection
    {
        // Get the languageHouses
        $result = $this->languageHouseService->getLanguageHouses();

        $collection = new ArrayCollection();
        foreach ($result['languageHouses'] as $languageHouse) {
            $resourceResult = $this->languageHouseService->handleResult($languageHouse);
            $resourceResult->setId(Uuid::getFactory()->fromString($languageHouse['id']));
            $collection->add($resourceResult);
        }

        return $collection;
    }

    public function userRolesByLanguageHouses(array $context): ?ArrayCollection
    {
        if (key_exists('languageHouseId', $context['args'])) {
            $languageHouseId = explode('/', $context['args']['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
        } else {
            throw new Exception('The languageHouseId was not specified');
        }

        $userRoles = $this->languageHouseService->getUserRolesByLanguageHouse($languageHouseId);

        $collection = new ArrayCollection();
        foreach ($userRoles as $userRole) {
            $resourceResult = $this->languageHouseService->handleResult(null, $userRole);
            $resourceResult->setId(Uuid::getFactory()->fromString($userRole['id']));
            $collection->add($resourceResult);
        }

        return $collection;
    }
}
