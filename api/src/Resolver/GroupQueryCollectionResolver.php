<?php

namespace App\Resolver;

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\EDUService;
use App\Service\StudentService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;

class GroupQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private EDUService $eduService;
    private StudentService $studentService;

    public function __construct(EDUService $eduService, StudentService $studentService)
    {
        $this->eduService = $eduService;
        $this->studentService = $studentService;
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
                return $this->createPaginator($this->eduService->getGroupsWithStatus($aanbiederId, 'ACTIVE'), $context['args']);
            case 'futureGroups':
                return $this->createPaginator($this->futureGroups(['course.organization' => $aanbiederId]), $context['args']);
            case 'completedGroups':
                return $this->createPaginator($this->eduService->getGroupsWithStatus($aanbiederId, 'COMPLETED'), $context['args']);
            default:
                return $this->createPaginator($this->getGroups(['course.organization' => $aanbiederId]), $context['args']);
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
