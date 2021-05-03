<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use Exception;

class RegistrationQueryItemResolver implements QueryItemResolverInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if(key_exists('registrationId', $context['info']->variableValues)){
            $registrationId = $context['info']->variableValues['registrationId'];
        } elseif (key_exists('id', $context['args'])) {
            $registrationId = $context['args']['id'];
        } else {
            throw new Exception('The registrationId / id was not specified');
        }

        $id = explode('/',$registrationId);
        if (is_array($id)) {
            $id = end($id);
        }
    }
}
