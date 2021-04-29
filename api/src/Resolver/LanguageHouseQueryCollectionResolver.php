<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\LanguageHouseService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Ramsey\Uuid\Uuid;

class LanguageHouseQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private LanguageHouseService $languageHouse;

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $result['result'] = [];

        // Get the languageHouses
        $result = array_merge($result, $this->languageHouse->getLanguageHouses());

        $collection = new ArrayCollection();
        if (isset($result['languageHouses'])) {
            // Now put together the expected result for Lifely:
            foreach ($result['languageHouses'] as &$languageHouse) {
                if (!isset($languageHouse['errorMessage'])) {
                    $resourceResult = $this->languageHouse->handleResult($languageHouse);
                    $resourceResult->setId(Uuid::getFactory()->fromString($languageHouse['id']));
                    $collection->add($resourceResult);
                    $languageHouse = $languageHouse['@id']; // Can be removed to show the entire body of all the learningNeeds when dumping $result
                }
            }
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $this->createPaginator($collection, $context['args']);
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
}
