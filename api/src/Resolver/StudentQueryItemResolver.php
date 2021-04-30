<?php


namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;
use Ramsey\Uuid\Uuid;
use App\Entity\Student;

class StudentQueryItemResolver implements QueryItemResolverInterface
{
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;

    public function __construct(CommongroundService $commonGroundService, StudentService $studentService){
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
        if(key_exists('studentId', $context['info']->variableValues)){
            $studentId = explode('/',$context['info']->variableValues['studentId']);
            if (is_array($studentId)) {
                $studentId = end($studentId);
            }
        } else {
            throw new Exception('The studentId was not specified');
        }

        $student = $this->studentService->getStudent($studentId);

        if (isset($student['participant']['id'])) {
            $resourceResult = $this->studentService->handleResult($student['person'], $student['participant']);
            $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
        }

        return $resourceResult;
    }

    public function newRefferedStudent(array $student): ?Student
    {

        return null;
    }
}
