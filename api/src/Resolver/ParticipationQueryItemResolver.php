<?php


namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\LearningNeedService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;
use Ramsey\Uuid\Uuid;

class ParticipationQueryItemResolver implements QueryItemResolverInterface
{
    private CommonGroundService $commonGroundService;
    private LearningNeedService $learningNeedService;

    public function __construct(CommongroundService $commonGroundService, LearningNeedService $learningNeedService){
        $this->commonGroundService = $commonGroundService;
        $this->learningNeedService = $learningNeedService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
//        $result['result'] = [];
//
//        if(key_exists('learningNeedId', $context['info']->variableValues)){
//            $learningNeedId = explode('/',$context['info']->variableValues['learningNeedId']);
//            if (is_array($learningNeedId)) {
//                $learningNeedId = end($learningNeedId);
//            }
//        } else {
//            throw new Exception('The learningNeedId was not specified');
//        }
//
//        $result = array_merge($result, $this->learningNeedService->getLearningNeed($learningNeedId));
//
//        if (isset($result['learningNeed'])) {
//            $resourceResult = $this->learningNeedService->handleResult($result['learningNeed']);
//            $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
//
//        return $resourceResult;
    }
}
