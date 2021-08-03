<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Provider;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\MrcService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProviderMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private ParameterBagInterface $parameterBagInterface;
    private CCService $ccService;
    private UcService $ucService;
    private EDUService $eduService;
    private MrcService $mrcService;

    /**
     * ProviderMutationResolver constructor.
     *
     * @param UcService    $ucService
     * @param LayerService $layerService
     */
    public function __construct(
        UcService $ucService,
        LayerService $layerService
    ) {
        $this->ccService = new CCService($layerService);
        $this->ucService = $ucService;
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
        $this->mrcService = new MrcService($layerService, $ucService);
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

    /**
     * Create a Provider.
     *
     * @param array $providerArray the resource data.
     *
     * @return Provider The resulting Provider properties
     */
    public function createProvider(array $providerArray): Provider
    {
        $type = 'Aanbieder';
        $result = $this->ccService->createOrganization($providerArray, $type);
        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);

        return $this->ccService->createOrganizationObject($result, $type);
    }

    /**
     * Update a Provider.
     *
     * @param array $input the input data.
     *
     * @return Provider The resulting Provider properties
     */
    public function updateProvider(array $input): Provider
    {
        $id = explode('/', $input['id']);
        if (is_array($id)) {
            $id = end($id);
        }
        $type = 'Aanbieder';

        $result = $this->ccService->updateOrganization($id, $input);
        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);

        return $this->ccService->createOrganizationObject($result, $type);
    }

    /**
     * Delete a Provider.
     *
     * @param array $input the input data.
     *
     * @throws \Exception
     *
     * @return Provider The resulting Provider properties
     */
    public function deleteProvider(array $input): ?Provider
    {
        $id = explode('/', $input['id']);
        if (is_array($id)) {
            $id = end($id);
        }

        //delete userGroups
        $this->ucService->deleteUserGroups($id);

        //delete employees
        $this->mrcService->deleteEmployees($id);

        //delete participants, this should just be done with the studentService!
        $programId = $this->eduService->deleteParticipants($id);

        $this->ccService->deleteOrganization($id, $programId);

        return null;
    }
}
