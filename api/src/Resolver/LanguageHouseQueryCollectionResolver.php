<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;

class LanguageHouseQueryCollectionResolver implements QueryCollectionResolverInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        //  var_dump($context['info']->operation->operation); //query
//        $collection = new ArrayCollection($collection);
        var_dump($context['args']);

        $collection = new ArrayCollection();
        //@TODO implement logic to find stuff and put it in the iterator
        $paginator = new ArrayPaginator($collection->toArray(), 0, count($collection));
        return $paginator;
    }
}
