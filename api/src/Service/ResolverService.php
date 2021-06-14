<?php

namespace App\Service;

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use Doctrine\Common\Collections\ArrayCollection;

class ResolverService
{
    /**
     * This function creates a paginator.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $collection An ArrayCollection where this paginator is ment for
     * @param array $args Array of arguments the paginator uses
     * @return \ApiPlatform\Core\DataProvider\ArrayPaginator Returns a ArrayPaginator
     */
    public function createPaginator(ArrayCollection $collection, array $args): ArrayPaginator
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
}
