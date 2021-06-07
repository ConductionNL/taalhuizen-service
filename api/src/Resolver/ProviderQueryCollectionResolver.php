<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ProviderService;
use App\Service\ResolverService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class ProviderQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private ProviderService $providerService;
    private ResolverService $resolverService;

    public function __construct(ProviderService $providerService, ResolverService $resolverService)
    {
        $this->providerService = $providerService;
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
                return $this->resolverService->createPaginator($this->providers($context), $context['args']);
            case 'userRolesByProviders':
                return $this->resolverService->createPaginator($this->userRolesByProviders($context), $context['args']);
            default:
                return $this->resolverService->createPaginator(new ArrayCollection(), $context['args']);
        }
    }

    public function providers(array $context): ?ArrayCollection
    {
        // Get the providers
        $result = $this->providerService->getProviders();

        $collection = new ArrayCollection();
        foreach ($result['providers'] as $provider) {
            $resourceResult = $this->providerService->handleResult($provider);
            $resourceResult->setId(Uuid::getFactory()->fromString($provider['id']));
            $collection->add($resourceResult);
        }

        return $collection;
    }

    public function userRolesByProviders(array $context): ?ArrayCollection
    {
        if (key_exists('providerId', $context['args'])) {
            $providerId = explode('/', $context['args']['providerId']);
            if (is_array($providerId)) {
                $providerId = end($providerId);
            }
        } else {
            throw new Exception('The providerId was not specified');
        }

        $userRoles = $this->providerService->getUserRolesByProvider($providerId);

        $collection = new ArrayCollection();
        foreach ($userRoles as $userRole) {
            $resourceResult = $this->providerService->handleResult(null, $userRole);
            $resourceResult->setId(Uuid::getFactory()->fromString($userRole['id']));
            $collection->add($resourceResult);
        }

        return $collection;
    }
}
