<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Entity\Group;
use App\Entity\LearningNeed;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;

class GroupQueryCollectionResolver implements QueryCollectionResolverInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $collection = new ArrayCollection();

        //@TODO implement logic to find stuff and put it in the iterator
        return $this->createPaginator($collection, $context['args']);

        if (!$collection instanceof Group && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'activeGroup':
                return $this->activeGroups($item);
            case 'futureGroup':
                return $this->futureGroups($context['info']->variableValues['input']);
            case 'completedGroup':
                return $this->participantsOfTheGroup($context['info']->variableValues['input']);
            case 'participantsOfTheGroup':
                return $this->completedGroups($context['info']->variableValues['input']);
            default:
                return $item;
        }
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

    public function activeGroups(array $group): ?Group
    {

        return null;
    }

    public function futureGroups(array $group): ?Group
    {

        return null;
    }

    public function participantsOfTheGroup(array $group): ?Group
    {

        return null;
    }

    public function completedGroups(array $group): ?Group
    {

        return null;
    }
}
