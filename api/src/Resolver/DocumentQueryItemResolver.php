<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\WRCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class DocumentQueryItemResolver implements QueryItemResolverInterface
{
    private WRCService $wrcService;

    public function __construct(
        CommonGroundService $commonGroundService,
        EntityManagerInterface $entityManager
    ) {
        $this->wrcService = new WRCService($entityManager, $commonGroundService);
    }

    /**
     * This function fetches a document with the given ID.
     *
     * @inheritDoc
     *
     * @param object|null $item    Object with the documents data
     * @param array       $context Context of the call
     *
     * @throws \Exception
     *
     * @return \App\Entity\Document|object Returns a Document object
     */
    public function __invoke($item, array $context)
    {
        if (isset($context['info']->variableValues['aanbiederEmployeeDocumentId']) && isset($context['info']->variableValues['studentDocumentId'])) {
            throw new Exception('Both studentDocumentId and aanbiederEmployeeDocumentId are given, please give one type of id');
        }
        if (isset($context['info']->variableValues['aanbiederEmployeeDocumentId'])) {
            $id = $context['info']->variableValues['aanbiederEmployeeDocumentId'];
            $idArray = explode('/', $id);
            $id = end($idArray);
        } elseif (isset($context['info']->variableValues['studentDocumentId'])) {
            $id = $context['info']->variableValues['studentDocumentId'];
            $idArray = explode('/', $id);
            $id = end($idArray);
        } else {
            throw new Exception('Both studentDocumentId and aanbiederEmployeeDocumentId are not given, please give one of these');
        }

        return $this->wrcService->getDocument($id);
    }
}
