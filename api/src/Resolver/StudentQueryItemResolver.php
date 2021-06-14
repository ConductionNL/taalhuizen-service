<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\StudentService;
use Exception;
use Ramsey\Uuid\Uuid;

class StudentQueryItemResolver implements QueryItemResolverInterface
{
    private StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * This function fetches a student with the given ID.
     *
     * @inheritDoc
     *
     * @param object|null $item    Object with the students data
     * @param array       $context Context of the call
     *
     * @throws \Exception
     *
     * @return object Returns a student object
     */
    public function __invoke($item, array $context): object
    {
        if (key_exists('studentId', $context['info']->variableValues)) {
            $studentId = $context['info']->variableValues['studentId'];
        } elseif (key_exists('id', $context['args'])) {
            $studentId = $context['args']['id'];
        } else {
            throw new Exception('The studentId was not specified');
        }
        $studentId = explode('/', $studentId);
        if (is_array($studentId)) {
            $studentId = end($studentId);
        }

        $student = $this->studentService->getStudent($studentId);

        if (isset($student['participant']['id'])) {
            $resourceResult = $this->studentService->handleResult($student);
            $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
        } else {
            throw new Exception('No participation id was found for this student!');
        }

        return $resourceResult;
    }
}
