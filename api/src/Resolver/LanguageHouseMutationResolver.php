<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\MrcService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LanguageHouseMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private ParameterBagInterface $parameterBagInterface;
    private CCService $ccService;
    private UcService $ucService;
    private EDUService $eduService;
    private MrcService $mrcService;

    /**
     * LanguageHouseMutationResolver constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param CommonGroundService    $commonGroundService
     * @param ParameterBagInterface  $parameterBagInterface
     * @param UcService              $ucService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CommonGroundService $commonGroundService,
        ParameterBagInterface $parameterBagInterface,
        UcService $ucService
    ) {
        $this->ccService = new CCService($entityManager, $commonGroundService);
        $this->ucService = $ucService;
        $this->eduService = new EDUService($commonGroundService);
        $this->mrcService = new MrcService($entityManager, $commonGroundService, $parameterBagInterface, $ucService);
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof LanguageHouse && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createLanguageHouse':
                return $this->createLanguageHouse($context['info']->variableValues['input']);
            case 'updateLanguageHouse':
                return $this->updateLanguageHouse($context['info']->variableValues['input']);
            case 'removeLanguageHouse':
                return $this->deleteLanguageHouse($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    /**
     * Create LanguageHouse.
     *
     * @param array $languageHouseArray the resource data.
     *
     * @return LanguageHouse The resulting LanguageHouse properties
     */
    public function createLanguageHouse(array $languageHouseArray): LanguageHouse
    {
        $type = 'Taalhuis';
        $result = $this->ccService->createOrganization($languageHouseArray, $type);
        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);

        return $this->ccService->createOrganizationObject($result, $type);
    }

    /**
     * Update LanguageHouse.
     *
     * @param array $input the input data.
     *
     * @return LanguageHouse The resulting LanguageHouse properties
     */
    public function updateLanguageHouse(array $input): LanguageHouse
    {
        $id = explode('/', $input['id']);
        if (is_array($id)) {
            $id = end($id);
        }
        $type = 'Taalhuis';

        $result = $this->ccService->updateOrganization($id, $input);
        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);

        return $this->ccService->createOrganizationObject($result, $type);
    }

    /**
     * Delete LanguageHouse.
     *
     * @param array $input the input data.
     *
     * @return ?LanguageHouse The resulting LanguageHouse properties
     */
    public function deleteLanguageHouse(array $input): ?LanguageHouse
    {
        $id = explode('/', $input['id']);
        if (is_array($id)) {
            $id = end($id);
        }

        //delete userGroups
        $this->ucService->deleteUserGroups($id);

        //delete employees
        $this->deleteEmployees($id);

        //delete participants
        $programId = $this->eduService->deleteParticipants($id);

        $this->ccService->deleteOrganization($id, $programId);

        return null;
    }

    /**
     * Deletes all employees of an organization.
     *
     * @param string $ccOrganizationId The organization to delete the employees of
     *
     * @return bool Whether the operation has been successful or not
     */
    public function deleteEmployees(string $ccOrganizationId): bool
    {
        $employees = $this->commonGroundService->getResourceList(['component' => 'mrc', 'type' => 'employees'], ['organization' => $ccOrganizationId])['hydra:member'];

        if ($employees > 0) {
            foreach ($employees as $employee) {
                $person = $this->commonGroundService->getResource($employee['person']);
                $this->commonGroundService->deleteResource(null, ['component'=>'cc', 'type' => 'people', 'id' => $person['id']]);
                $this->commonGroundService->deleteResource(null, ['component'=>'mrc', 'type'=>'employees', 'id'=>$employee['id']]);
            }
        }

        return true;
    }
}
