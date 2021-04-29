<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
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
        $result['result'] = [];

        if(key_exists('languageHouseId', $context['info']->variableValues)){
            $languageHouseId = explode('/',$context['info']->variableValues['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
        } else {
            throw new Exception('The learningNeedId was not specified');
        }

        $result['languageHouse'] = array_merge($result, $this->languageHouseService->getLanguageHouse($languageHouseId));
        var_dump($result['languageHouse']);

        if (isset($result['languageHouse'])) {
            $resourceResult = $this->languageHouseService->handleResult($result['languageHouse']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['languageHouse']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }
}
