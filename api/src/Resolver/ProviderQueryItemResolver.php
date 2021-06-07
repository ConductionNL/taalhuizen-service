<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\Provider;
use App\Service\ProviderService;
use Exception;
use Ramsey\Uuid\Uuid;

class ProviderQueryItemResolver implements QueryItemResolverInterface
{
    private ProviderService $providerService;

    public function __construct(ProviderService $providerService)
    {
        $this->providerService = $providerService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (isset($context['info']->variableValues['providerId'])) {
            $id = $context['info']->variableValues['providerId'];
            $idArray = explode('/', $id);
            $id = end($idArray);
        }

        return $this->getProvider($id);
    }

    public function getProvider(string $id): Provider
    {
        $result['result'] = [];

        $id = explode('/', $id);
        if (is_array($id)) {
            $id = end($id);
        }

        $result = array_merge($result, $this->providerService->getProvider($id));

        if (isset($result['provider'])) {
            $resourceResult = $this->providerService->handleResult($result['provider']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['provider']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }
}
