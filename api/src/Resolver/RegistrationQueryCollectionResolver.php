<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ResolverService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class RegistrationQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;
    private ResolverService $resolverService;

    public function __construct(CommongroundService $commonGroundService, StudentService $studentService, ResolverService $resolverService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
        $this->resolverService = $resolverService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (!key_exists('languageHouseId', $context['args'])) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'registrations':
                return $this->resolverService->createPaginator($this->students($context), $context['args']);

            default:
                return $this->resolverService->createPaginator(new ArrayCollection(), $context['args']);

        }
    }

    public function students(array $context): ?ArrayCollection
    {
        if (key_exists('languageHouseId', $context['args'])) {
            $languageHouseId = explode('/', $context['args']['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
        } else {
            throw new Exception('The languageHouseId was not specified');
        }

        $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        $query = ['program.provider' => $languageHouseUrl];

        $students = $this->studentService->getStudents($query, true);

        $collection = new ArrayCollection();
        // Now put together the expected result for Lifely:
        foreach ($students as $student) {
            if (isset($student['participant']['id'])) {
                $resourceResult = $this->studentService->handleResult($student, true);
                $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
                $collection->add($resourceResult);
            }
        }

        return $collection;
    }
}
