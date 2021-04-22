<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Group;
use App\Entity\LanguageHouse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class GroupMutationResolver implements MutationResolverInterface
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
        if (!$item instanceof Group && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createGroup':
                return $this->createGroup($context['info']->variableValues['input']);
            case 'updateGroup':
                return $this->updateGroup($context['info']->variableValues['input']);
            case 'removeGroup':
                return $this->deleteGroup($context['info']->variableValues['input']);
            case 'activeGroups':
                return $this->activeGroups($context['info']->variableValues['input']);
            case 'futureGroups':
                return $this->futureGroups($context['info']->variableValues['input']);
            case 'changeGroupTeachers':
                return $this->changeGroupTeachers($context['info']->variableValues['input']);
            case 'participants':
                return $this->participants($context['info']->variableValues['input']);
            case 'completedGroups':
                return $this->completedGroups($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createGroup(array $groupArray): Group
    {
        $group = new Group();
        $this->entityManager->persist($group);
        return $group;
    }

    public function updateGroup(array $input): Group
    {
        $id = explode('/',$input['id']);
        $group = new Group();


        $this->entityManager->persist($group);
        return $group;
    }

    public function deleteGroup(array $group): ?Group
    {

        return null;
    }

    public function activeGroups(array $group): ?Group
    {

        return null;
    }

    public function futureGroups(array $group): ?Group
    {

        return null;
    }

    public function changeGroupTeachers(array $group): ?Group
    {

        return null;
    }

    public function participants(array $group): ?Group
    {

        return null;
    }

    public function completedGroups(array $group): ?Group
    {

        return null;
    }
}
