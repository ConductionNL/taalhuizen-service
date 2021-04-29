<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\CCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ramsey\Uuid\Uuid;

class ProviderQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CCService $CCService;
    private CommonGroundService $commonGroundService;

    public function __construct(CommongroundService $commonGroundService, CCService $CCService){
        $this->CCService = $CCService;
        $this->commonGroundService = $commonGroundService;
    }
    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $result['result'] = [];

        if(key_exists('id', $context['args'])){
            $id = explode('/',$context['args']['id']);
            if (is_array($id)) {
                $id = end($id);
            }
        } else {
            throw new Exception('The studentId was not specified');
        }

        // Get the learningNeeds of this student from EAV
        $result = array_merge($result, $this->CCService->getProviders($id));

        $collection = new ArrayCollection();
        if (isset($result['providers'])) {
            // Now put together the expected result for Lifely:
            foreach ($result['providers'] as &$provider) {
                if (!isset($learningNeed['errorMessage'])) {
                    $resourceResult = $this->CCService->handleResult($provider, $studentId);
                    $resourceResult->setId(Uuid::getFactory()->fromString($provider['id']));
                    $collection->add($resourceResult);
                    $provider = $provider['@id']; // Can be removed to show the entire body of all the learningNeeds when dumping $result
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
