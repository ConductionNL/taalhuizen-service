<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Employee;
use App\Service\MrcService;
use App\Service\ParticipationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class EmployeeMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private MrcService $mrcService;
    private ParticipationService $participationService;

    /**
     * EmployeeMutationResolver constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param MrcService             $mrcService
     * @param ParticipationService   $participationService
     */
    public function __construct(EntityManagerInterface $entityManager, MrcService $mrcService, ParticipationService $participationService)
    {
        $this->entityManager = $entityManager;
        $this->mrcService = $mrcService;
        $this->participationService = $participationService;
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
            case 'addMentoredParticipationToEmployee':
                return $this->addMentorToParticipation($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    /**
     * Creates an employee.
     *
     * @param array $employeeArray The input data for an employee
     *
     * @throws Exception Thrown when the EAV cannot handle the employee
     *
     * @return Employee The resulting employee
     */
    public function createEmployee(array $employeeArray): Employee
    {
        return $this->mrcService->createEmployee($employeeArray);
    }

    /**
     * @param array $input The input data for the employee
     *
     * @throws Exception Thrown when the EAV cannot handle the employee
     *
     * @return Employee The resulting employee
     */
    public function updateEmployee(array $input): Employee
    {
        $id = explode('/', $input['id']);

        return $this->mrcService->updateEmployeeToObject(end($id), $input);
    }

    /**
     * Deletes a user.
     *
     * @param array $input The data needed to delete the employee
     *
     * @throws Exception Thrown when the EAV cannot handle the deletion of the employee
     *
     * @return Employee|null The resulting employee (usually null)
     */
    public function deleteEmployee(array $input): ?Employee
    {
        $id = explode('/', $input['id']);
        $this->mrcService->deleteEmployee(end($id));

        return null;
    }

    /**
     * Adds an employee as mentor to a participation.
     *
     * @param array $input The inputted data needed to perform this operation
     *
     * @throws Exception Thrown if the EAV cannot handle the action
     *
     * @return Employee The resulting employee object
     */
    public function addMentorToParticipation(array $input): Employee
    {
        $participationId = explode('/', $input['participationId']);
        $aanbiederEmployeeId = explode('/', $input['aanbiederEmployeeId']);

        return $this->participationService->addMentoredParticipationToEmployee(end($participationId), end($aanbiederEmployeeId));
    }
}
