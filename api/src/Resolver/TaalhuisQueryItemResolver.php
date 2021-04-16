<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;

class TaalhuisQueryItemResolver implements QueryItemResolverInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        echo 'congrats!';
        die;
        // TODO: Implement __invoke() method.
    }
}
