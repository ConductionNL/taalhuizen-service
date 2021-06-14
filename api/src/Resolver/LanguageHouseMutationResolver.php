<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\MrcService;
use App\Service\UcService;

class LanguageHouseMutationResolver implements MutationResolverInterface
{
    private CCService $ccService;
    private UcService $ucService;
    private MrcService $mrcService;
    private EDUService $eduService;

    /**
     * LanguageHouseMutationResolver constructor.
     * @param CCService $ccService
     * @param UcService $ucService
     * @param MrcService $mrcService
     * @param EDUService $eduService
     */
    public function __construct(
        CCService $ccService,
        UcService $ucService,
        MrcService $mrcService,
        EDUService $eduService
    ) {
        $this->ccService = $ccService;
        $this->ucService = $ucService;
        $this->mrcService = $mrcService;
        $this->eduService = $eduService;
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
     * Create LanguageHouse
     *
     * @param array $languageHouseArray the resource data.
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
     * Update LanguageHouse
     *
     * @param array $input the input data.
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
     * Delete LanguageHouse
     *
     * @param array $input the input data.
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
        $this->mrcService->deleteEmployees($id);

        //delete participants
        $programId = $this->eduService->deleteParticipants($id);

        $this->ccService->deleteOrganization($id, $programId);

        return null;
    }
}
