<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\WRCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;

class DocumentQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private WRCService $wrcService;
    private CommonGroundService $cgs;

    public function __construct(WRCService $wrcService, CommonGroundService $cgs){
        $this->wrcService = $wrcService;
        $this->cgs = $cgs;
    }
    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (isset($context['info']->variableValues['studentId']) && isset($context['info']->variableValues['aanbiederEmployeeId'])) {
            throw new Exception('Both studentId and aanbiederEmployeeId are given, please give one type of id');
        }
        if (isset($context['info']->variableValues['studentId'])) {
            $id = $context['info']->variableValues['studentId'];
            if (strpos($id, '/') !== false) {
                $idArray = explode('/', $id);
                $id = end($idArray);
                $contact = $this->cgs->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id]);
            }
        } elseif (isset($context['info']->variableValues['aanbiederEmployeeId'])) {
            $id = $context['info']->variableValues['aanbiederEmployeeId'];
            if (strpos($id, '/') !== false) {
                $idArray = explode('/', $id);
                $id = end($idArray);
                $contact = $this->cgs->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $id]);
            }
        } else {
            $contact = null;
        }

        $collection = $this->wrcService->getDocuments($contact);
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
