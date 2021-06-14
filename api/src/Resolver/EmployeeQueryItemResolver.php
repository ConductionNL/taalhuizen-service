<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\MrcService;
use Exception;

class EmployeeQueryItemResolver implements QueryItemResolverInterface
{
    private MrcService $mrcService;

    /**
     * EmployeeQueryItemResolver constructor.
     * @param MrcService $mrcService
     */
    public function __construct(MrcService $mrcService)
    {
        $this->mrcService = $mrcService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (key_exists('employeeId', $context['info']->variableValues)) {
            $employeeId = $context['info']->variableValues['employeeId'];
        } elseif (key_exists('id', $context['args'])) {
            $employeeId = $context['args']['id'];
        } else {
            throw new Exception('The employeeId / id was not specified');
        }

        $id = explode('/', $employeeId);
        if (is_array($id)) {
            $id = end($id);
        }

        return $this->mrcService->getEmployee($id);
    }
}
