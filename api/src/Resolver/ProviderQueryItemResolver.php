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
            $providerId = explode('/',$context['info']->variableValues['providerId']);
            if (is_array($providerId)) {
                $providerId = end($providerId);
            }
        } else {
            throw new Exception('The learningNeedId was not specified');
        }

        $result['provider'] = array_merge($result, $this->providerService->getProvider($providerId));
        var_dump($result['provider']);

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
