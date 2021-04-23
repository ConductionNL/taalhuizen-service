<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\TestResult;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Test;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class TestResultMutationResolver implements MutationResolverInterface
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
        if (!$item instanceof TestResult && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createTestResult':
                return $this->createTestResult($context['info']->variableValues['input']);
            case 'updateTestResult':
                return $this->updateTestResult($context['info']->variableValues['input']);
            case 'removeTestResult':
                return $this->deleteTestResult($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createTestResult(array $testResultArray): TestResult
    {
        $testResult = new TestResult();
        $this->entityManager->persist($testResult);
        return $testResult;
    }

    public function updateTestResult(array $input): TestResult
    {
        $id = explode('/',$input['id']);
        $testResult = new TestResult();


        $this->entityManager->persist($testResult);
        return $testResult;
    }

    public function deleteTestResult(array $testResult): ?TestResult
    {

        return null;
    }
}
