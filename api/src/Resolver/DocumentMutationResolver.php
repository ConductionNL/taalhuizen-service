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

class DocumentMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
//        var_dump($context['info']->operation->name->value);
        var_dump($context['info']->variableValues);
//        var_dump(get_class($item));
        if (!$item instanceof LanguageHouse && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
//        var_dump($context['info']->operation->name->value);
        switch($context['info']->operation->name->value){
            case 'createDocument':
                return $this->createDocument($context['info']->variableValues['input']);
            case 'updateDocument':
                return $this->updateDocument($context['info']->variableValues['input']);
            case 'removeDocument':
                return $this->deleteDocument($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createDocument(array $documentArray): Document
    {
        $document = new Document();
        $this->entityManager->persist($document);
        return $document;
    }

    public function updateDocument(array $input): Document
    {
        $id = explode('/',$input['id']);
        $document = new Document();


        $this->entityManager->persist($document);
        return $document;
    }

    public function deleteDocument(array $document): ?Document
    {

        return null;
    }
}
