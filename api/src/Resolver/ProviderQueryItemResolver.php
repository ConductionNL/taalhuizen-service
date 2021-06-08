<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\Provider;
use App\Service\CCService;
use App\Service\ProviderService;
use Exception;
use Ramsey\Uuid\Uuid;

class ProviderQueryItemResolver implements QueryItemResolverInterface
{
    private ProviderService $providerService;
    private CCService $ccService;

    public function __construct(
        ProviderService $providerService,
        CCService $ccService
    )
    {
        $this->providerService = $providerService;
        $this->ccService = $ccService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
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
        return $this->ccService->getOrganization($id, $type = 'Aanbieder');
    }
}
