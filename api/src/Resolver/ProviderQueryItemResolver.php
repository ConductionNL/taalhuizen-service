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

    public function __construct(ProviderService $providerService){
        $this->providerService = $providerService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        switch($context['info']->operation->name->value){
            case 'userRolesByProvider':
                if(key_exists('providerId', $context['info']->variableValues)){
                    $providerId = $context['info']->variableValues['providerId'];
                } elseif (key_exists('id', $context['args'])) {
                    $providerId = $context['args']['id'];
                } else {
                    throw new Exception('The providerId / id was not specified');
                }
                return $this->userRolesByProvider($providerId);
            default:
                if(key_exists('providerId', $context['info']->variableValues)){
                    $providerId = $context['info']->variableValues['providerId'];
                } elseif (key_exists('id', $context['args'])) {
                    $providerId = $context['args']['id'];
                } else {
                    throw new Exception('The providerId / id was not specified');
                }
                return $this->getProvider($providerId);
        }
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
            $resourceResult = $this->providerService->createProviderObject($result['provider']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['provider']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }

    public function userRolesByProvider(string $providerId): Provider
    {

        $result['result'] = [];

        $providerId = explode('/', $providerId);
        if (is_array($providerId)) {
            $providerId = end($providerId);
        }

        $userGroups = $this->providerService->getProviderUserGroups($providerId);
        var_dump($userGroups);


        return $result;
    }

}
