<?php

namespace App\Resolver;

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class StudentQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;

    public function __construct(CommongroundService $commonGroundService, StudentService $studentService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception;
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (!key_exists('languageHouseId', $context['args']) &&
            !key_exists('providerId', $context['args']) &&
            !key_exists('groupId', $context['args']) &&
            !key_exists('aanbiederEmployeeId', $context['args'])) {
            throw new Exception('Invalid request, please provide an id to filter students by');
        }
        switch ($context['info']->operation->name->value) {
            case 'students':
                return $this->createPaginator($this->students($context), $context['args']);
            case 'newRefferedStudents':
                return $this->createPaginator($this->newRefferedStudents($context), $context['args']);
            case 'activeStudents':
                return $this->createPaginator($this->activeStudents($context), $context['args']);
            case 'completedStudents':
                return $this->createPaginator($this->completedStudents($context), $context['args']);
            case 'groupStudents':
                return $this->createPaginator($this->groupStudents($context), $context['args']);
            case 'aanbiederEmployeeMenteesStudents':
                return $this->createPaginator($this->aanbiederEmployeeMenteesStudents($context), $context['args']);
            default:
                return $this->createPaginator(new ArrayCollection(), $context['args']);
        }
    }

    public function createPaginator(ArrayCollection $collection, array $args)
    {
        if (key_exists('first', $args)) {
            $maxItems = $args['first'];
            $firstItem = 0;
        } elseif (key_exists('last', $args)) {
            $maxItems = $args['last'];
            $firstItem = (count($collection) - 1) - $maxItems;
        } else {
            $maxItems = count($collection);
            $firstItem = 0;
        }
        if (key_exists('after', $args)) {
            $firstItem = base64_decode($args['after']);
        } elseif (key_exists('before', $args)) {
            $firstItem = base64_decode($args['before']) - $maxItems;
        }

        return new ArrayPaginator($collection->toArray(), $firstItem, $maxItems);
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
        $query = [
            'program.provider' => $languageHouseUrl,
            'status'           => 'accepted',
        ];

        return $this->handleStudentCollection($query);
    }

    public function handleStudentCollection($query)
    {
        $students = $this->studentService->getStudents($query);

        $collection = new ArrayCollection();
        foreach ($students as $student) {
            if (isset($student['participant']['id'])) {
                $resourceResult = $this->studentService->handleResult($student['person'], $student['participant'], $student['employee'], $student['registrarPerson'], $student['registrarOrganization'], $student['registrarMemo']);
                $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
                $collection->add($resourceResult);
            }
        }

        return $collection;
    }

    public function newRefferedStudents(array $context): ?ArrayCollection
    {
        if (key_exists('providerId', $context['args'])) {
            $providerId = explode('/', $context['args']['providerId']);
            if (is_array($providerId)) {
                $providerId = end($providerId);
            }
        } else {
            throw new Exception('The providerId was not specified');
        }

        return $this->studentService->getStudentsWithStatus($providerId, 'REFERRED');
    }

    public function activeStudents(array $context): ?ArrayCollection
    {
        if (key_exists('providerId', $context['args'])) {
            $providerId = explode('/', $context['args']['providerId']);
            if (is_array($providerId)) {
                $providerId = end($providerId);
            }
        } else {
            throw new Exception('The providerId was not specified');
        }

        return $this->studentService->getStudentsWithStatus($providerId, 'ACTIVE');
    }

    public function completedStudents(array $context): ?ArrayCollection
    {
        if (key_exists('providerId', $context['args'])) {
            $providerId = explode('/', $context['args']['providerId']);
            if (is_array($providerId)) {
                $providerId = end($providerId);
            }
        } else {
            throw new Exception('The providerId was not specified');
        }

        return $this->studentService->getStudentsWithStatus($providerId, 'COMPLETED');
    }

    public function groupStudents(array $context): ?ArrayCollection
    {
        if (key_exists('groupId', $context['args'])) {
            $groupId = explode('/', $context['args']['groupId']);
            if (is_array($groupId)) {
                $groupId = end($groupId);
            }
        } else {
            throw new Exception('The groupId was not specified');
        }

        $query = [
            'participantGroups.id' => $groupId,
            'status'               => 'accepted',
        ];

        return $this->handleStudentCollection($query);
    }

    public function aanbiederEmployeeMenteesStudents(array $context): ?ArrayCollection
    {
        if (key_exists('aanbiederEmployeeId', $context['args'])) {
            $aanbiederEmployeeId = explode('/', $context['args']['aanbiederEmployeeId']);
            if (is_array($aanbiederEmployeeId)) {
                $aanbiederEmployeeId = end($aanbiederEmployeeId);
            }
        } else {
            throw new Exception('The aanbiederEmployeeId was not specified');
        }

        $mentorUrl = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $aanbiederEmployeeId]);
        $query = [
            'mentor' => $mentorUrl,
            'status' => 'accepted',
        ];

        return $this->handleStudentCollection($query);
    }
}
