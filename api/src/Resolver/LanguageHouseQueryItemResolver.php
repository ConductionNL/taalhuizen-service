<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\LanguageHouse;
use App\Service\LanguageHouseService;
use Exception;
use Ramsey\Uuid\Uuid;

class LanguageHouseQueryItemResolver implements QueryItemResolverInterface
{
    private LanguageHouseService $languageHouseService;

    public function __construct(LanguageHouseService $languageHouseService){
        $this->languageHouseService = $languageHouseService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        switch($context['info']->operation->name->value){
            case 'userRolesByLanguageHouse':
                if(key_exists('languageHouseId', $context['info']->variableValues)){
                    $languageHouseId = $context['info']->variableValues['languageHouseId'];
                } elseif (key_exists('id', $context['args'])) {
                    $languageHouseId = $context['args']['id'];
                } else {
                    throw new Exception('The languageHouseId / id was not specified');
                }
                return $this->userRolesByLanguageHouse($languageHouseId);
            default:
                if(key_exists('languageHouseId', $context['info']->variableValues)){
                    $languageHouseId = $context['info']->variableValues['languageHouseId'];
                } elseif (key_exists('id', $context['args'])) {
                    $languageHouseId = $context['args']['id'];
                } else {
                    throw new Exception('The languageHouseId / id was not specified');
                }
                return $this->getLanguageHouse($languageHouseId);
        }
    }

    public function getLanguageHouse(string $id): LanguageHouse
    {
        $result['result'] = [];

        $id = explode('/', $id);
        if (is_array($id)) {
            $id = end($id);
        }

        $result = array_merge($result, $this->languageHouseService->getLanguageHouse($id));

        if (isset($result['languageHouse'])) {
            $resourceResult = $this->languageHouseService->createLanguageHouseObject($result['languageHouse']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['languageHouse']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }

    public function userRolesByLanguageHouse(string $id): LanguageHouse
    {

        $result['result'] = [];

        $id = explode('/', $id);
        if (is_array($id)) {
            $id = end($id);
        }

        return $result;
    }
}
