<?php


namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\ParticipationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;
use Ramsey\Uuid\Uuid;

class ParticipationQueryItemResolver implements QueryItemResolverInterface
{
    private CommonGroundService $commonGroundService;
    private ParticipationService $participationService;

    public function __construct(CommongroundService $commonGroundService, ParticipationService $participationService){
        $this->commonGroundService = $commonGroundService;
        $this->participationService = $participationService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
        $result['result'] = [];

        if(key_exists('participationId', $context['info']->variableValues)){
            $participationId = $context['info']->variableValues['participationId'];
        } elseif (key_exists('id', $context['args'])) {
            $participationId = $context['args']['id'];
        } else {
            throw new Exception('The participationId was not specified');
        }
        $participationId = explode('/',$participationId);
        if (is_array($participationId)) {
            $participationId = end($participationId);
        }

        $result = array_merge($result, $this->participationService->getParticipation($participationId));

        if (isset($result['participation'])) {
            $resourceResult = $this->participationService->handleResult($result['participation']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['participation']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }
}
