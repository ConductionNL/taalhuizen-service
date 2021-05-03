<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Document;
use App\Entity\LanguageHouse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use App\Service\WRCService;

class DocumentMutationResolver implements MutationResolverInterface
{
    private WRCService $wrcService;

    public function __construct(WRCService $wrcService){
        $this->wrcService = $wrcService;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Document && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createDocument':
                return $this->wrcService->createDocument($context['info']->variableValues['input']);
            case 'downloadDocument':
                return $this->wrcService->downloadDocument($context['info']->variableValues['input']);
            case 'deleteDocument':
                return $this->wrcService->deleteDocument($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }
}
