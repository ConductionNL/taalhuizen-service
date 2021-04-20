<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Dossier;
use App\Entity\LanguageHouse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DossierMutationResolver implements MutationResolverInterface
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
        if (!$item instanceof Dossier && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createDossier':
                return $this->createDossier($context['info']->variableValues['input']);
            case 'updateDossier':
                return $this->updateDossier($context['info']->variableValues['input']);
            case 'removeDossier':
                return $this->deleteDossier($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createDossier(array $dossierArray): Dossier
    {
        $dossier = new Dossier();
        $this->entityManager->persist($dossier);
        return $dossier;
    }

    public function updateDossier(array $input): Dossier
    {
        $id = explode('/',$input['id']);
        $dossier = new Dossier();


        $this->entityManager->persist($dossier);
        return $dossier;
    }

    public function deleteDossier(array $dossier): ?Dossier
    {

        return null;
    }
}
