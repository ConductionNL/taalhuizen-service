<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
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

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, LanguageHouseService $languageHouseService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->languageHouseService = $languageHouseService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
//        var_dump($context['info']->operation->name->value);
//        var_dump($context['info']->variableValues);
        if (!$item instanceof LanguageHouse && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createLanguageHouse':
                return $this->createLanguageHouse($item);
            case 'updateLanguageHouse':
                return $this->updateLanguageHouse($context['info']->variableValues['input']);
            case 'removeLanguageHouse':
                return $this->deleteLanguageHouse($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createLanguageHouse(LanguageHouse $resource): LanguageHouse
    {
        $result['result'] = [];

        // get all DTO info...
        $languageHouse = $this->dtoToLanguageHouse($resource);

        $result = array_merge($result, $this->languageHouseService->createLanguageHouse($languageHouse));
        var_dump($result);

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->languageHouseService->handleResult($languageHouse);
        $resourceResult->setId(Uuid::getFactory()->fromString($result['languageHouse']['id']));

        // If any error was catched throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        return $resourceResult;
    }

    public function updateLanguageHouse(array $input): LanguageHouse
    {
        $result['result'] = [];

        // If LanguageHouseUrl or LanguageHouseId is set generate the id for it, needed for eav calls later
        $languageHouseId = explode('/', $input['id']);
        if (is_array($languageHouseId)) {
            $languageHouseId = end($languageHouseId);
        }

        // Transform input info to LanguageHouse body...
        $languageHouse = $this->inputToLanguageHouse($input);

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save LanguageHouse
            $result = array_merge($result, $this->languageHouseService->updateLanguageHouse($languageHouse, $languageHouseId));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->languageHouseService->handleResult($languageHouse);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['languageHouse']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function deleteLanguageHouse(array $input): ?LanguageHouse
    {
        $result['result'] = [];

        $id = explode('/', $input['id']);
        $id = end($id);
        $result = array_merge($result, $this->languageHouseService->deleteLanguageHouse($id));

        $result['result'] = False;
        if (isset($result['languageHouse'])){
            $result['result'] = True;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return null;
    }

    private function dtoToLanguageHouse(LanguageHouse $resource)
    {
        // Get all info from the dto for creating/updating a LanguageHouse and return the body for this
        $languageHouse['address'] = $resource->getAddress();
        $languageHouse['email'] = $resource->getEmail();
        $languageHouse['phoneNumber'] = $resource->getPhoneNumber();
        $languageHouse['name'] = $resource->getName();

        return $languageHouse;
    }

    private function inputToLanguageHouse(array $input)
    {
        // Get all info from the input array for updating a LanguageHouse and return the body for this
        $languageHouse['address'] = $input['address'];
        $languageHouse['email'] = $input['email'];
        $languageHouse['phoneNumber'] = $input['phoneNumber'];
        $languageHouse['name'] = $input['name'];

        return $languageHouse;
    }
}
