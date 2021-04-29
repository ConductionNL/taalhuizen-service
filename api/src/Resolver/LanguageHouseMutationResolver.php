<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
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

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
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

        $result = array_merge($result, $this->saveLanguageHouse($languageHouse));
        var_dump($result);

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->handleResult($result['languageHouse']);
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
        $languageHouseId = null;
        if (isset($input['languageHouseUrl'])) {
            $languageHouseId = $this->commonGroundService->getUuidFromUrl($input['languageHouseUrl']);
        } else {
            $languageHouseId = explode('/',$input['id']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
        }

        // Transform input info to LanguageHouse body...
        $languageHouse = $this->inputToLanguageHouse($input);

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save LanguageHouse
            $result = array_merge($result, $this->saveLanguageHouse($result['languageHouse'], $languageHouseId));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->handleResult($result['languageHouse']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['languageHouse']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function deleteLanguageHouse(array $languageHouse): ?LanguageHouse
    {
        return null;
    }

    public function saveLanguageHouse($languageHouse, $languageHouseId = null)
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now = $now->format('d-m-Y H:i:s');

        // Save the languageHouse
        if (isset($languageHouseId)) {
            // Update
            $languageHouse['dateModified'] = $now;
            $languageHouseWrc = $this->commonGroundService->saveResource($languageHouse, ['component' => 'wrc', 'type' => 'organizations']);
            $languageHouseCC = $this->commonGroundService->saveResource($languageHouse, ['component' => 'cc', 'type' => 'organizations']);
        } else {
            // Create
            $languageHouse['dateCreated'] = $now;
            $languageHouse['dateModified'] = $now;

            $languageHouseWrc = $this->commonGroundService->saveResource($languageHouse, ['component' => 'wrc', 'type' => 'organizations']);

            $languageHouse['addresses'][]['name'] = 'Address of '.$languageHouse['name'];
            $languageHouse['addresses'][] = $languageHouse['address'];

            $languageHouse['emails'][]['name'] = 'Email of '.$languageHouse['name'];
            $languageHouse['emails'][]['email'] = $languageHouse['email'];

            $languageHouse['telephones'][]['name'] = 'Telephone of '.$languageHouse['name'];
            $languageHouse['telephones'][]['telephone'] = $languageHouse['phoneNumber'];

            //add source organization to cc organization
            $languageHouse['sourceOrganization'] = $languageHouseWrc['@id'];
            $languageHouseCC = $this->commonGroundService->saveResource($languageHouse, ['component' => 'cc', 'type' => 'organizations']);

            //add contact to wrc organization
            $languageHouse['contact'] = $languageHouseCC['@id'];
            $languageHouseWrc = $this->commonGroundService->saveResource($languageHouse, ['component' => 'wrc', 'type' => 'organizations']);

        }
        // Add $providerCC and $providerWrc to the $result['providerCC'] and $result['providerWrc'] because this is convenient when testing or debugging (mostly for us)
        $result['languageHouse'] = $languageHouseCC;

        return $result;
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

    private function handleResult($languageHouse)
    {
        $resource = new LanguageHouse();
        $resource->setAddress($languageHouse['address']);
        $resource->setEmail($languageHouse['email']);
        $resource->setPhoneNumber($languageHouse['phoneNumber']);
        $resource->setName($languageHouse['name']);

        $this->entityManager->persist($resource);
        return $resource;
    }

    private function inputToLanguageHouse(array $input)
    {
        // Get all info from the input array for updating a LanguageHouse and return the body for this
        $languageHouse['name'] = $input['name'];
        $languageHouse['addresses'][] = $input['address'];
        $languageHouse['emails'][]['email'] = $input['email'];
        $languageHouse['telephones'][]['telephones'] = $input['phoneNumber'];

        return $languageHouse;
    }
}
