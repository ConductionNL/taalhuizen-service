<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Provider;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\MrcService;
use App\Service\UcService;
use Doctrine\ORM\EntityManagerInterface;

class ProviderMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CCService $ccService;
    private UcService $ucService;
    private EDUService $eduService;
    private MrcService $mrcService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CCService $ccService,
        UcService $ucService,
        EDUService $eduService,
        MrcService $mrcService
    ) {
        $this->entityManager = $entityManager;
        $this->ccService = $ccService;
        $this->ucService = $ucService;
        $this->eduService = $eduService;
        $this->mrcService = $mrcService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Provider && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createProvider':
                return $this->createProvider($context['info']->variableValues['input']);
            case 'updateProvider':
                return $this->updateProvider($context['info']->variableValues['input']);
            case 'removeProvider':
                return $this->deleteProvider($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createProvider(array $providerArray): Provider
    {
        $type = 'Aanbieder';
        $result = $this->ccService->createOrganization($providerArray, $type);
        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);

        return $this->ccService->createOrganizationObject($result, $type);
    }

    public function updateProvider(array $input): Provider
    {
        $type = 'Aanbieder';
        $id = explode('/', $input['id']);

        $result = $this->ccService->updateOrganization(end($id), $input, $type);
        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);

        return $this->ccService->createOrganizationObject($result, $type);
    }

    public function deleteProvider(array $input): ?Provider
    {
        $id = explode('/', $input['id']);

        //delete userGroups
        $this->ucService->deleteUserGroups($id);

        //delete employees
        $this->mrcService->deleteEmployees($id);

        //delete participants
        $programId = $this->eduService->deleteParticipants($id);

        $this->ccService->deleteOrganization(end($id), $programId);

        return null;
    }
}
