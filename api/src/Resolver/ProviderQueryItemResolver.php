<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\ProviderService;
use Exception;
use Ramsey\Uuid\Uuid;

class ProviderQueryItemResolver implements QueryItemResolverInterface
{
    private ProviderService $providerService;

    public function __construct(ProviderService $providerService){
        $this->providerService = $providerService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        $result['result'] = [];

        if(key_exists('providerId', $context['info']->variableValues)){
            $providerId = $context['info']->variableValues['providerId'];
        } elseif (key_exists('id', $context['args'])) {
            $providerId = $context['args']['id'];
        } else {
            throw new Exception('The providerId / id was not specified');
        }

        $id = explode('/',$providerId);
        if (is_array($id)) {
            $id = end($id);
        }

        $result = array_merge($result, $this->providerService->getProvider($id));

        if (isset($result['provider'])) {
            $resourceResult = $this->providerService->createProviderObject($result['provider']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['provider']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }
}
