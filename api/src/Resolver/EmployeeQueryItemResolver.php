<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\MrcService;

class EmployeeQueryItemResolver implements QueryItemResolverInterface
{
    private MrcService $mrcService;

    public function __construct(MrcService $mrcService){
        $this->mrcService = $mrcService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        $id = explode('/',$context['info']->variableValues['employeeId']);
        if (is_array($id)) {
            $id = end($id);
        }
        return $this->mrcService->getEmployee($id);
    }
}
