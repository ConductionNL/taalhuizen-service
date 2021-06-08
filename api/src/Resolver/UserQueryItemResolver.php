<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\User;
use App\Service\UcService;
use Symfony\Component\HttpFoundation\RequestStack;

class UserQueryItemResolver implements QueryItemResolverInterface
{
    private RequestStack $requestStack;
    private UcService $ucService;

    public function __construct(RequestStack $requestStack, UcService $ucService)
    {
        $this->requestStack = $requestStack;
        $this->ucService = $ucService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        switch ($context['info']->operation->name->value) {
            case 'currentUser':
                return $this->getCurrentUser();
            default:
                if (key_exists('userId', $context['info']->variableValues)) {
                    $userId = $context['info']->variableValues['userId'];
                } elseif (key_exists('id', $context['args'])) {
                    $userId = $context['args']['id'];
                } else {
                    throw new Exception('The userId was not specified');
                }

                return $this->getUser($userId);
        }
    }

    public function getUser(string $id): User
    {
        $id = explode('/', $id);
        $id = end($id);

        return $this->ucService->getUser($id);
    }

    public function getCurrentUser(): User
    {
        $token = str_replace('Bearer ', '', $this->requestStack->getCurrentRequest()->headers->get('Authorization'));
        $payload = $this->ucService->validateJWTAndGetPayload($token);

        return $this->getUser($payload['userId']);
    }
}
