<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Document;
use App\Entity\Employee;
use App\Entity\LanguageHouse;
use App\Entity\User;
use App\Service\MrcService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class EmployeeMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private MrcService $mrcService;

    public function __construct(EntityManagerInterface $entityManager, MrcService $mrcService){
        $this->entityManager = $entityManager;
        $this->mrcService = $mrcService;
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
        return $this->mrcService->createEmployee($employeeArray);
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
