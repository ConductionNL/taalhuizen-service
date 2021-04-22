<?php


namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Exception;
use Ramsey\Uuid\Uuid;

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
        $result['result'] = [];

        if(key_exists('studentId', $context['info']->variableValues)){
            $studentId = explode('/',$context['info']->variableValues['studentId']);
            if (is_array($studentId)) {
                $studentId = end($studentId);
            }
        } else {
            throw new Exception('The studentId was not specified');
        }

        $result = array_merge($result, $this->studentService->getStudent($studentId));

        if (isset($result['student'])) {
            $resourceResult = $this->studentService->handleResult($result['student']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['student']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }
}
