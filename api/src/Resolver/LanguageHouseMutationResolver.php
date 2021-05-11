<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
use App\Service\CCService;
use App\Service\LanguageHouseService;
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
    private CommonGroundService $commonGroundService;
    private LanguageHouseService $languageHouseService;
    private CCService $ccService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommongroundService $commonGroundService,
        LanguageHouseService $languageHouseService,
        CCService $ccService
    )
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->languageHouseService = $languageHouseService;
        $this->ccService = $ccService;
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
        return $this->ccService->createOrganization($languageHouseArray, $type);
    }

    public function updateLanguageHouse(array $input): LanguageHouse
    {
        $id = explode('/',$input['id']);
        $type = 'Taalhuis';
        return $this->ccService->updateOrganization(end($id), $input, $type);
    }

    public function deleteLanguageHouse(array $input): ?LanguageHouse
    {
        $id = explode('/',$input['id']);
        $this->ccService->deleteOrganization(end($id));
        return null;
    }
}
