<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\CCService;
use Exception;

class LanguageHouseQueryItemResolver implements QueryItemResolverInterface
{
    private CCService $ccService;

    /**
     * LanguageHouseQueryItemResolver constructor.
     *
     * @param CCService $ccService
     */
    public function __construct(
        CCService $ccService
    ) {
        $this->ccService = $ccService;
    }

    /**
     * Get a LanguageHouse with the given id.
     *
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (key_exists('languageHouseId', $context['info']->variableValues)) {
            $languageHouseId = $context['info']->variableValues['languageHouseId'];
        } elseif (key_exists('id', $context['args'])) {
            $languageHouseId = $context['args']['id'];
        } else {
            throw new Exception('The languageHouseId / id was not specified');
        }

        $id = explode('/', $languageHouseId);
        if (is_array($id)) {
            $id = end($id);
        }

        return $this->ccService->getOrganization($id, $type = 'Taalhuis');
    }
}
