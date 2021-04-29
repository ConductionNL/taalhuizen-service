<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\LanguageHouseService;
use Exception;
use Ramsey\Uuid\Uuid;

class LanguageHouseQueryItemResolver implements QueryItemResolverInterface
{
    private LanguageHouseService $languageHouse;

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        $result['result'] = [];

        if(key_exists('languageHouseId', $context['info']->variableValues)){
            $languageHouseId = explode('/',$context['info']->variableValues['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
        } else {
            throw new Exception('The learningNeedId was not specified');
        }

        $result = array_merge($result, $this->languageHouse->getLanguageHouse($languageHouseId));
        var_dump($result);

        if (isset($result['languageHouse'])) {
            $resourceResult = $this->languageHouse->handleResult($result['languageHouse']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['languageHouse']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }
}
