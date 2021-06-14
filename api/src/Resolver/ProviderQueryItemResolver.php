<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\CCService;
use Exception;

class ProviderQueryItemResolver implements QueryItemResolverInterface
{
    private CCService $ccService;

    /**
     * ProviderQueryItemResolver constructor.
     *
     * @param CCService $ccService
     */
    public function __construct(
        CCService $ccService
    ) {
        $this->ccService = $ccService;
    }

    /**
     * Get a Provider with the given id.
     *
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (key_exists('providerId', $context['info']->variableValues)) {
            $providerId = $context['info']->variableValues['providerId'];
        } elseif (key_exists('id', $context['args'])) {
            $providerId = $context['args']['id'];
        } else {
            throw new Exception('The providerId / id was not specified');
        }

        $id = explode('/', $providerId);
        if (is_array($id)) {
            $id = end($id);
        }

        return $this->ccService->getOrganization($id, $type = 'Aanbieder');
    }
}
