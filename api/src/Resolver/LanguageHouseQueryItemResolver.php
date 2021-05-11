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
        if (isset($context['info']->variableValues['languageHouseId'])) {
            $id = $context['info']->variableValues['languageHouseId'];
            $idArray = explode('/', $id);
            $id = end($idArray);
        }
            return $this->getLanguageHouse($id);
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
