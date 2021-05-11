<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\LanguageHouse;
use App\Service\CCService;
use App\Service\LanguageHouseService;
use Exception;
use Ramsey\Uuid\Uuid;

class LanguageHouseQueryItemResolver implements QueryItemResolverInterface
{
    private LanguageHouseService $languageHouseService;
    private CCService $ccService;

    public function __construct(
        LanguageHouseService $languageHouseService,
        CCService $ccService
    ){
        $this->languageHouseService = $languageHouseService;
        $this->ccService = $ccService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if(key_exists('languageHouseId', $context['info']->variableValues)){
            $languageHouseId = $context['info']->variableValues['languageHouseId'];
        } elseif (key_exists('id', $context['args'])) {
            $languageHouseId = $context['args']['id'];
        } else {
            throw new Exception('The languageHouseId / id was not specified');
        }

        $id = explode('/',$languageHouseId);
        if (is_array($id)) {
            $id = end($id);
        }
        return $this->ccService->getOrganization($id, $type = 'Taalhuis');
    }
}
