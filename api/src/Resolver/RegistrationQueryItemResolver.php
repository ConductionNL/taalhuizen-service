<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;
use Ramsey\Uuid\Uuid;

class RegistrationQueryItemResolver implements QueryItemResolverInterface
{
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;

    public function __construct(CommongroundService $commonGroundService, StudentService $studentService){
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if(key_exists('studentId', $context['info']->variableValues)){
            $studentId = $context['info']->variableValues['studentId'];
        } elseif (key_exists('id', $context['args'])) {
            $studentId = $context['args']['id'];
        } else {
            throw new Exception('The studentId was not specified');
        }
        $studentId = explode('/',$studentId);
        if (is_array($studentId)) {
            $studentId = end($studentId);
        }

        $student = $this->studentService->getStudent($studentId);

        if (isset($student['participant']['id'])) {
            $resourceResult = $this->studentService->handleResult($student['person'], $student['participant'], $student['employee'], $student['registrarPerson'], $student['registrarOrganization'], $student['registrarMemo'], true);
            $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
        }

        return $resourceResult;
    }
}
