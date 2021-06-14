<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ResolverService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Ramsey\Uuid\Uuid;

class StudentQueryCollectionResolver implements QueryCollectionResolverInterface
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
     * This function determines what function to execute next based on the context.
     *
     * @inheritDoc
     *
     * @param object|null $item    Post object
     * @param array       $context Information about post
     *
     * @throws \Exception
     *
     * @return iterable|object|null Returns a iterable
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
                return $this->resolverService->createPaginator($this->students($context), $context['args']);
            case 'newRefferedStudents':
                return $this->resolverService->createPaginator($this->newRefferedStudents($context), $context['args']);
            case 'activeStudents':
                return $this->resolverService->createPaginator($this->activeStudents($context), $context['args']);
            case 'completedStudents':
                return $this->resolverService->createPaginator($this->completedStudents($context), $context['args']);
            case 'groupStudents':
                return $this->resolverService->createPaginator($this->groupStudents($context), $context['args']);
            case 'aanbiederEmployeeMenteesStudents':
                return $this->resolverService->createPaginator($this->aanbiederEmployeeMenteesStudents($context), $context['args']);
            default:
                return $this->resolverService->createPaginator(new ArrayCollection(), $context['args']);

        }
    }

    /**
     * This function checks if the students have a language house id.
     *
     * @param array $context Array with context
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|null
     */
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

    /**
     * This function handles the student collection query.
     *
     * @param array $query Array with query
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection Returns ArrayCollection with students
     */
    public function handleStudentCollection(array $query): ArrayCollection
    {
        $students = $this->studentService->getStudents($query);

        $collection = new ArrayCollection();
        foreach ($students as $student) {
            if (isset($student['participant']['id'])) {
                $resourceResult = $this->studentService->handleResult($student);
                $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
                $collection->add($resourceResult);
            }
        }

        return $collection;
    }

    /**
     * This function checks if new referred students have a referred status.
     *
     * @param array $context Array with context
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|null Returns ArrayCollection with students
     */
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

    /**
     * This function checks if students have a active status.
     *
     * @param array $context Array with context
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|null Returns ArrayCollection with students
     */
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

    /**
     * This function checks if students have a completed status.
     *
     * @param array $context Array with context
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|null Returns ArrayCollection with students
     */
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

    /**
     * This function checks if students have a group.
     *
     * @param array $context Array with context
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|null Returns ArrayCollection with students
     */
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

    /**
     * This function checks if student have a aanbieder employee.
     *
     * @param array $context Array with context
     *
     * @throws \Exception
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|null Returns ArrayCollection with students
     */
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
