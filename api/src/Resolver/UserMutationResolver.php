<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\User;
use App\Service\UcService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private UcService $ucService;

    public function __construct(EntityManagerInterface $entityManager, UcService $ucService){
        $this->entityManager = $entityManager;
        $this->ucService = $ucService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof User && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createUser':
                return $this->createUser($context['info']->variableValues['input']);
            case 'updateUser':
                return $this->updateUser($context['info']->variableValues['input']);
            case 'removeUser':
                return $this->deleteUser($context['info']->variableValues['input']);
            case 'loginUser':
                return $this->login($context['info']->variableValues['input']);
            case 'requestPasswordResetUser':
                return $this->requestPasswordReset($context['info']->variableValues['input']);
            case 'resetPasswordUser':
                return $this->resetPassword($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createUser(array $userArray): User
    {
        return $this->ucService->createUser($userArray);
    }

    public function updateUser(array $input): User
    {
        $id = explode('/',$input['id']);
        $id = end($id);
        return $this->ucService->updateUser($id, $input);
    }

    public function deleteUser(array $user): ?User
    {
        $id = explode('/',$user['id']);
        $id = end($id);
        $this->ucService->deleteUser($id);
        return null;
    }

    public function resetPassword(array $input): User
    {
        return $this->ucService->updatePasswordWithToken($input['email'], $input['token'], $input['password']);
    }

    public function login(array $user): User
    {
        $userObject = new User();
        $userObject->setToken($this->ucService->login($user['username'], $user['password']));
        $this->entityManager->persist($userObject);

        return $userObject;
    }

    public function requestPasswordReset(array $input): ?User
    {
        $userObject = new User();
        $userObject->setToken($this->ucService->requestPasswordReset($input['email']));
        $this->entityManager->persist($userObject);

        return $userObject;
    }
}
