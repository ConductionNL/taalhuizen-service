<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Entity\Group;
use App\Entity\LearningNeed;
use App\Service\EDUService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;

class GroupQueryCollectionResolver implements QueryCollectionResolverInterface
{

    private EDUService $eduService;

    public function __construct(EDUService $eduService)
    {
        $this->eduService = $eduService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $providerId = isset($context['args']['aanbiederId']) ? $context['args']['aanbiederId'] : null;
        switch($context['info']->operation->name->value){
            case 'activeGroups':
                $collection = $this->activeGroups(['course.organization' => $providerId]);
                break;
            case 'futureGroups':
                $collection = $this->futureGroups(['course.organization' => $providerId]);
                break;
//            case 'completedGroups':
//                $collection = $this->participantsOfTheGroup($context['info']->variableValues['input']);
//                break;
            case 'completedGroups':
                $collection = $this->completedGroups(['course.organization' => $providerId]);
                break;
            default:
                $collection = $this->getGroups(['course.organization' => $providerId]);
        }
        return $this->createPaginator($collection, $context['args']);
    }

    public function getGroups(?array $query = []): ArrayCollection
    {
        return new ArrayCollection($this->eduService->getGroups($query));
    }

    public function createPaginator(ArrayCollection $collection, array $args){
        if(key_exists('first', $args)){
            $maxItems = $args['first'];
            $firstItem = 0;
        } elseif(key_exists('last', $args)) {
            $maxItems = $args['last'];
            $firstItem = (count($collection) - 1) - $maxItems;
        } else {
            $maxItems = count($collection);
            $firstItem = 0;
        }
        if(key_exists('after', $args)){
            $firstItem = base64_decode($args['after']);
        } elseif(key_exists('before', $args)){
            $firstItem = base64_decode($args['before']) - $maxItems;
        }
        return new ArrayPaginator($collection->toArray(), $firstItem, $maxItems);
    }

    public function activeGroups(?array $query = []): ArrayCollection
    {
        $now = new DateTime('now');
        $now = $now->format("Ymd");
        $query = array_merge($query, ['endDate[strictly_after]' => $now, 'startDate[before]' => $now]);
        return $this->getGroups($query);
    }

    public function futureGroups(?array $query = []): ArrayCollection
    {
        $now = new DateTime('now');
        $now = $now->format("Ymd");
        $query = array_merge($query, ['startDate[strictly_after]' => $now]);
        return $this->getGroups($query);
    }

    public function participantsOfTheGroup(): ?ArrayCollection
    {

        return null;
    }

    public function completedGroups(?array $query = []): ?ArrayCollection
    {
        $now = new DateTime('now');
        $now = $now->format("Ymd");
        $query = array_merge($query, ['endDate[strictly_before]' => $now]);
        return $this->getGroups($query);
    }
}
