<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ProviderService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Ramsey\Uuid\Uuid;

class ProviderQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private ProviderService $providerService;

    public function __construct(ProviderService $providerService)
    {
        $this->providerService = $providerService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        switch ($context['info']->operation->name->value) {
            case 'providers':
                return $this->createPaginator($this->providers($context), $context['args']);
            case 'userRolesByProviders':
                return $this->createPaginator($this->userRolesByProviders($context), $context['args']);
            default:
                return $this->createPaginator(new ArrayCollection(), $context['args']);
        }
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

    public function providers(array $context): ?ArrayCollection
    {
        // Get the providers
        $providers = $this->providerService->getProviders();

        $collection = new ArrayCollection();
        foreach ($providers as $provider) {
            $resourceResult = $this->providerService->handleResult($provider);
            $resourceResult->setId(Uuid::getFactory()->fromString($provider['id']));
            $collection->add($resourceResult);
        }
        return $collection;
    }

    public function userRolesByProviders(array $context): ?ArrayCollection
    {
        if(key_exists('providerId', $context['args'])){
            $providerId = explode('/',$context['args']['providerId']);
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
