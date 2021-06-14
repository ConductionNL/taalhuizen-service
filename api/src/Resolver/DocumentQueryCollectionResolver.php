<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\ResolverService;
use App\Service\WRCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class DocumentQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CommonGroundService $cgs;
    private EntityManagerInterface $em;
    private WRCService $wrcService;
    private ResolverService $resolverService;

    public function __construct
    (
        CommonGroundService $cgs,
        EntityManagerInterface $em
    )
    {
        $this->cgs = $cgs;
        $this->wrcService = new WRCService($em, $cgs);
        $this->resolverService = new ResolverService();
    }

    /**
     * This function creates a paginator.
     *
     * @inheritDoc
     * @param iterable $collection Collection of documents
     * @param array $context Context of the call
     * @return iterable Returns a paginator
     * @throws \Exception
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (isset($context['info']->variableValues['studentId']) && isset($context['info']->variableValues['aanbiederEmployeeId'])) {
            throw new Exception('Both studentId and aanbiederEmployeeId are given, please give one type of id');
        }
        if (key_exists('studentId', $context['args'])) {
            $studentId = explode('/', $context['args']['studentId']);
            if (is_array($studentId)) {
                $studentId = end($studentId);
                $contact = $this->cgs->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $studentId]);
            }
        } elseif (key_exists('aanbiederEmployeeId', $context['args'])) {
            $aanbiederEmployeeId = explode('/', $context['args']['aanbiederEmployeeId']);
            if (is_array($aanbiederEmployeeId)) {
                $aanbiederEmployeeId = end($aanbiederEmployeeId);
                $contact = $this->cgs->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $aanbiederEmployeeId]);
            }
        } else {
            $contact = null;
        }

        $collection = $this->wrcService->getDocuments($contact);

        return $this->resolverService->createPaginator($collection, $context['args']);
    }
}
