<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\Student;

class StudentQueryItemResolver implements QueryItemResolverInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        // TODO: Implement __invoke() method.
        if (!$item instanceof Student && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'newRefferedStudent':
                return $this->newRefferedStudent($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function newRefferedStudent(array $student): ?Student
    {

        return null;
    }
}
