<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\User;
use App\Service\UcService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class UserMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private UcService $ucService;

    public function __construct(EntityManagerInterface $entityManager, UcService $ucService)
    {
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
        switch ($context['info']->operation->name->value) {
            case 'createUser':
                return $this->createUser($context['info']->variableValues['input']);
            case 'updateUser':
                return $this->updateUser($context['info']->variableValues['input']);
            case 'removeUser':
                return $this->deleteUser($context['info']->variableValues['input']);
            case 'loginUser':
                return $this->login($context['info']->variableValues['input']);
            case 'logoutUser':
                return $this->logout();
            case 'requestPasswordResetUser':
                return $this->requestPasswordReset($context['info']->variableValues['input']);
            case 'resetPasswordUser':
                return $this->resetPassword($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    /**
     * Creates a user
     *
     * @param array $userArray the input data for the user
     * @return User The resulting user object
     */
    public function createUser(array $userArray): User
    {
        return $this->ucService->createUser($userArray);
    }

    /**
     * Updates a user
     * @param array $input The input data for the user
     * @return User The resulting user object
     */
    public function updateUser(array $input): User
    {
        $id = explode('/', $input['id']);
        $id = end($id);

        return $this->ucService->updateUser($id, $input);
    }

    /**
     * Deletes a user
     *
     * @param array $user The input data for the user
     * @return User|null The resulting user if the delete fails
     */
    public function deleteUser(array $user): ?User
    {
        $id = explode('/', $user['id']);
        $id = end($id);
        $this->ucService->deleteUser($id);

        return null;
    }

    /**
     * Sets a new password for a user
     *
     * @param array $input The input for the user
     * @return User The resulting user
     * @throws Exception Thrown if the JWT is invalid
     */
    public function resetPassword(array $input): User
    {
        return $this->ucService->updatePasswordWithToken($input['email'], $input['token'], $input['password']);
    }

    /**
     * Logs in a user
     *
     * @param array $user The username/password combination to log in the user
     * @return User The resulting user
     */
    public function login(array $user): User
    {
        $userObject = new User();
        $userObject->setToken($this->ucService->login($user['username'], $user['password']));
        $this->entityManager->persist($userObject);

        return $userObject;
    }

    /**
     * Requests a password reset for a user
     * @param array $input The input needed to retrieve a password reset token
     * @return User|null The resulting user object
     */
    public function requestPasswordReset(array $input): ?User
    {
        $userObject = new User();
        $userObject->setToken($this->ucService->createPasswordResetToken($input['email']));
        $this->entityManager->persist($userObject);

        return $userObject;
    }

    /**
     * Logs out the user by invalidating the user token
     * @return User|null The result of the logout action, usually null
     * @throws \Psr\Cache\InvalidArgumentException Thrown when the cache cannot invalidate the token
     */
    public function logout(): ?User
    {
        $this->ucService->logout();

        return null;
    }
}
