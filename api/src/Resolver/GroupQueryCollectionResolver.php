<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\EDUService;
use App\Service\ResolverService;
use App\Service\StudentService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;

class GroupQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private EDUService $eduService;
    private StudentService $studentService;
    private ResolverService $resolverService;

    public function __construct(EDUService $eduService, StudentService $studentService, ResolverService $resolverService)
    {
        $this->eduService = $eduService;
        $this->studentService = $studentService;
        $this->resolverService = $resolverService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (key_exists('aanbiederId', $context['args'])) {
            $aanbiederId = explode('/', $context['args']['aanbiederId']);
            if (is_array($aanbiederId)) {
                $aanbiederId = end($aanbiederId);
            }
        } else {
            $aanbiederId = null;
        }

        switch ($context['info']->operation->name->value) {
            case 'activeGroups':
                return $this->resolverService->createPaginator($this->eduService->getGroupsWithStatus($aanbiederId, 'ACTIVE'), $context['args']);
            case 'futureGroups':
                return $this->resolverService->createPaginator($this->futureGroups(['course.organization' => $aanbiederId]), $context['args']);
            case 'completedGroups':
                return $this->resolverService->createPaginator($this->eduService->getGroupsWithStatus($aanbiederId, 'COMPLETED'), $context['args']);
            default:
                return $this->resolverService->createPaginator($this->getGroups(['course.organization' => $aanbiederId]), $context['args']);
        }
    }

    public function participantsOfGroup(?string $groupId = null): ArrayCollection
    {
        if (!$groupId) {
            throw new Exception('Cannot retrieve participants of null');
        }

        return new ArrayCollection($this->studentService->getStudents(['participantGroup.id' => $groupId]));
    }

    public function getGroups(?array $query = []): ArrayCollection
    {
        return new ArrayCollection($this->eduService->getGroups($query));
    }

    public function activeGroups(?array $query = []): ArrayCollection
    {
        $now = new DateTime('now');
        $now = $now->format('Ymd');
        $query = array_merge($query, ['endDate[strictly_after]' => $now, 'startDate[before]' => $now]);

        return $this->getGroups($query);
    }

    public function futureGroups(?array $query = []): ArrayCollection
    {
        $now = new DateTime('now');
        $now = $now->format('Ymd');
        $query = array_merge($query, ['startDate[strictly_after]' => $now]);

        return $this->getGroups($query);
    }

    public function completedGroups(?array $query = []): ?ArrayCollection
    {
        $now = new DateTime('now');
        $now = $now->format('Ymd');
        $query = array_merge($query, ['endDate[strictly_before]' => $now]);

        return $this->getGroups($query);
    }
}
