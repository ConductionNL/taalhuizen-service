<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Document;
use App\Entity\Employee;
use App\Entity\LanguageHouse;
use App\Entity\Student;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class StudentMutationResolver implements MutationResolverInterface
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
        if (!$item instanceof Student && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createStudent':
                return $this->createStudent($context['info']->variableValues['input']);
            case 'updateStudent':
                return $this->updateStudent($context['info']->variableValues['input']);
            case 'removeStudent':
                return $this->deleteStudent($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createStudent(array $studentArray): Student
    {
        $student = new Student();
        $this->entityManager->persist($student);
        return $student;
    }

    public function updateStudent(array $input): Student
    {
        $id = explode('/',$input['id']);
        $student = new Student();


        $this->entityManager->persist($student);
        return $student;
    }

    public function deleteStudent(array $student): ?Student
    {

        return null;
    }
}
