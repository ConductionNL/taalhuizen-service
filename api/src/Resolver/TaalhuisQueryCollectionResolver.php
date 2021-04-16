<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use Doctrine\Common\Collections\ArrayCollection;

class TaalhuisQueryCollectionResolver implements QueryCollectionResolverInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        //  var_dump($context['info']->operation->operation); //query
        $collection = new ArrayCollection($collection);
        var_dump($context['args']);
        //@TODO implement logic to find stuff

        return $collection;
    }
}
