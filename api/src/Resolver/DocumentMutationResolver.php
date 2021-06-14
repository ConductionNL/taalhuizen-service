<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Document;
use App\Service\WRCService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;

class DocumentMutationResolver implements MutationResolverInterface
{
    private WRCService $wrcService;
    private CommonGroundService $cgs;
    private EntityManagerInterface $em;

    public function __construct(
        CommonGroundService $cgs,
        EntityManagerInterface $em
    ) {
        $this->wrcService = new WRCService($em, $cgs);
    }

    /**
     * This function determines what function to execute next based on the context.
     *
     * @inheritDoc
     *
     * @param object|null $item    Post object
     * @param array       $context Information about post
     *
     * @throws \Exception
     *
     * @return \App\Entity\Document|object|null Returns a Document object
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Document && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createDocument':
                return $this->wrcService->createDocument($context['info']->variableValues['input']);
            case 'downloadDocument':
                return $this->wrcService->downloadDocument($context['info']->variableValues['input']);
            case 'removeDocument':
                return $this->wrcService->removeDocument($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }
}
