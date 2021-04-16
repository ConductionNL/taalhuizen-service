<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LanguageHouseMutationResolver implements MutationResolverInterface
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
//        var_dump($context['info']->variableValues);
//        var_dump(get_class($item));
        if (!$item instanceof LanguageHouse && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
//        var_dump($context['info']->operation->name->value);
        switch($context['info']->operation->name->value){
            case 'createTaalhuis':
                return $this->createTaalhuis($item);
            case 'updateTaalhuis':
                return $this->updateTaalhuis($context['info']->variableValues['input']);
            case 'removeTaalhuis':
                return $this->deleteTaalhuis($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createTaalhuis(LanguageHouse $taalhuis): LanguageHouse
    {
        $this->entityManager->persist($taalhuis);
        return $taalhuis;
    }

    public function updateTaalhuis(array $input): LanguageHouse
    {
        $id = explode('/',$input['id']);
        $taalhuis = new LanguageHouse();
        $taalhuis->setId(Uuid::getFactory()->fromString(end($id)));
        $taalhuis->setEmail($input['email']);
        $taalhuis->setName($input['name']);

        $this->entityManager->persist($taalhuis);
        return $taalhuis;
    }

    public function deleteTaalhuis(array $taalhuis): ?LanguageHouse
    {

        return null;
    }
}
