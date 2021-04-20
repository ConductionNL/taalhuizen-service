<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\LanguageHouse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserMutationResolver implements MutationResolverInterface
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
            case 'createUser':
                return $this->createUser($context['info']->variableValues['input']);
            case 'updateUser':
                return $this->updateUser($context['info']->variableValues['input']);
            case 'removeUser':
                return $this->deleteUser($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createUser(array $userArray): User
    {
        $user = new User();
        $this->entityManager->persist($user);
        return $user;
    }

    public function updateUser(array $input): User
    {
        $id = explode('/',$input['id']);
        $user = new User();


        $this->entityManager->persist($user);
        return $user;
    }

    public function deleteUser(array $LanguageHouse): ?User
    {

        return null;
    }
}
