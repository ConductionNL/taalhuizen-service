<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Provider;
use App\Service\CCService;
use App\Service\ProviderService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SensioLabs\Security\Exception\HttpException;

class ProviderMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private ProviderService $providerService;
    private CCService $ccService;
    private UcService $ucService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommongroundService $commonGroundService,
        ProviderService $providerService,
        CCService $ccService,
        UcService $ucService
    )
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->providerService = $providerService;
        $this->ccService = $ccService;
        $this->ucService = $ucService;
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
        return $this->ccService->createOrganization($providerArray);
    }

    public function updateProvider(array $input): Provider
    {
        $id = explode('/',$input['id']);
        return $this->ccService->updateOrganization(end($id), $input);
    }

    public function deleteProvider(array $input): ?Provider
    {
        $id = explode('/',$input['id']);
        $this->ccService->deleteOrganization(end($id));
        return null;
    }
}
