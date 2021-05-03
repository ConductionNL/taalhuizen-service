<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\Group;
use App\Service\EDUService;
use Exception;

class GroupQueryItemResolver implements QueryItemResolverInterface
{
    private EDUService $eduService;

    public function __construct(EDUService $eduService){
        $this->eduService = $eduService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if(key_exists('groupId', $context['info']->variableValues)){
            $groupId = $context['info']->variableValues['groupId'];
        } elseif (key_exists('id', $context['args'])) {
            $groupId = $context['args']['id'];
        } else {
            throw new Exception('The groupId / id was not specified');
        }

        $id = explode('/',$groupId);
        if (is_array($id)) {
            $id = end($id);
        }
        return $this->eduService->getGroup($id);
    }

    public function participantsOfTheGroup()
    {
        $participants = [];

        return $participants;
    }

}
