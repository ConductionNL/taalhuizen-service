<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Document;
use App\Entity\Employee;
use App\Entity\LanguageHouse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class EmployeeMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof LanguageHouse && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
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
        $employee = new Employee();
        $this->entityManager->persist($employee);
        return $employee;
    }

    public function updateEmployee(array $input): Employee
    {
        $id = explode('/',$input['id']);
        $employee = new Employee();


        $this->entityManager->persist($employee);
        return $employee;
    }

    public function deleteEmployee(array $employee): ?Employee
    {

        return null;
    }
}
