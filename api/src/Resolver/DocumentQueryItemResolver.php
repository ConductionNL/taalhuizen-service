<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;

class DocumentQueryItemResolver implements QueryItemResolverInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if(key_exists('documentId', $context['info']->variableValues)){
            $documentId = $context['info']->variableValues['documentId'];
        } elseif (key_exists('id', $context['args'])) {
            $documentId = $context['args']['id'];
        } else {
            throw new Exception('The documentId was not specified');
        }
        $documentId = explode('/',$documentId);
        if (is_array($documentId)) {
            $documentId = end($documentId);
        }

        // TODO: Implement __invoke() method.
    }
}
