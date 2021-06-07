<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Employee;
use App\Service\MrcService;
use Doctrine\ORM\EntityManagerInterface;

class EmployeeMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private MrcService $mrcService;

    public function __construct(EntityManagerInterface $entityManager, MrcService $mrcService)
    {
        $this->entityManager = $entityManager;
        $this->mrcService = $mrcService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Employee && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createEmployee':
                return $this->createEmployee($context['info']->variableValues['input']);
            case 'updateEmployee':
                return $this->updateEmployee($context['info']->variableValues['input']);
            case 'removeEmployee':
                return $this->deleteEmployee($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createEmployee(array $employeeArray): Employee
    {
        return $this->mrcService->createEmployee($employeeArray);
    }

    public function updateEmployee(array $input): Employee
    {
        $id = explode('/', $input['id']);

        return $this->mrcService->updateEmployee(end($id), $input);
    }

    public function deleteEmployee(array $input): ?Employee
    {
        $id = explode('/', $input['id']);
        $this->mrcService->deleteEmployee(end($id));

        return null;
    }
}
