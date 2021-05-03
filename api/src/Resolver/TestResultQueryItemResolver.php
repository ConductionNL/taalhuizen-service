<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;

class TestResultQueryItemResolver implements QueryItemResolverInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if(key_exists('testResultId', $context['info']->variableValues)){
            $testResultId = $context['info']->variableValues['testResultId'];
        } elseif (key_exists('id', $context['args'])) {
            $testResultId = $context['args']['id'];
        } else {
            throw new Exception('The testResultId was not specified');
        }
        $testResultId = explode('/',$testResultId);
        if (is_array($testResultId)) {
            $testResultId = end($testResultId);
        }

        // TODO: Implement __invoke() method.
    }
}
