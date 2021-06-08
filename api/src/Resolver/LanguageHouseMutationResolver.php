<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\LanguageHouseService;
use App\Service\MrcService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SensioLabs\Security\Exception\HttpException;


class LanguageHouseMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private CCService $ccService;
    private UcService $ucService;
    private MrcService $mrcService;
    private EDUService $eduService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CCService $ccService,
        UcService $ucService,
        MrcService $mrcService,
        EDUService $eduService
    )
    {
        $this->entityManager = $entityManager;
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
        switch($context['info']->operation->name->value){
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

    public function createLanguageHouse(array $languageHouseArray): LanguageHouse
    {
        $type = 'Taalhuis';
        $result = $this->ccService->createOrganization($languageHouseArray, $type);
        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);

        return $this->ccService->createOrganizationObject($result, $type);
    }

    public function updateLanguageHouse(array $input): LanguageHouse
    {
        $id = explode('/',$input['id']);
        $type = 'Taalhuis';

        $result = $this->ccService->updateOrganization(end($id), $input, $type);
        $this->eduService->saveProgram($result);
        $this->ucService->createUserGroups($result, $type);

        return $this->ccService->createOrganizationObject($result, $type);
    }

    public function deleteLanguageHouse(array $input): ?LanguageHouse
    {
        $id = explode('/',$input['id']);

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
