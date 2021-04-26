<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Provider;
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

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService)
    {
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
//        var_dump(get_class($item));
        if (!$item instanceof Provider && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
//        var_dump($context['info']->operation->name->value);
        switch ($context['info']->operation->name->value) {
            case 'createProvider':
                return $this->createProvider($item);
            case 'updateProvider':
                return $this->updateProvider($context['info']->variableValues['input']);
            case 'removeProvider':
                return $this->deleteProvider($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createProvider(Provider $resource): Provider
    {
        $result['result'] = [];

        // get all DTO info...
        $provider = $this->dtoToProvider($resource);

        $result = array_merge($result, $this->saveProvider($provider));
        var_dump($result);

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->handleResult($result['aanbieder']);
        $resourceResult->setId(Uuid::getFactory()->fromString($result['aanbieder']['id']));

        // If any error was catched throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }

    public function updateProvider(array $input): Provider
    {
        $id = explode('/', $input['id']);
        $provider = new Provider();
        $provider->setId(Uuid::getFactory()->fromString(end($id)));
        $provider->setEmail($input['email']);
        $provider->setName($input['name']);

        $this->entityManager->persist($provider);
        return $provider;
    }

    public function deleteProvider(array $provider): ?Provider
    {

        return null;
    }

    public function saveProvider($provider, $providerId = null)
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now = $now->format('d-m-Y H:i:s');

        // Save the aanbieder
        if (isset($providerId)) {
            // Update
            $provider['dateModified'] = $now;
            $providerWrc = $this->commonGroundService->saveResource($provider, ['component' => 'wrc', 'type' => 'organizations']);
            $providerCC = $this->commonGroundService->saveResource($provider, ['component' => 'cc', 'type' => 'organizations']);
        } else {
            // Create
            $provider['dateCreated'] = $now;
            $provider['dateModified'] = $now;

            $providerWrc = $this->commonGroundService->saveResource($provider, ['component' => 'wrc', 'type' => 'organizations']);

//            $provider['addresses'] = $provider['address'];
            $provider['emails']['name'] = 'Email of ...';
            $provider['emails']['email'] = $provider['email'];
            $provider['sourceOrganization'] = $providerWrc['@id'];
            $providerCC = $this->commonGroundService->saveResource($provider, ['component' => 'cc', 'type' => 'organizations']);
        }

        // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        $result['provider'] = $providerCC;

        return $result;
    }

    private function dtoToProvider(Provider $resource)
    {
        // Get all info from the dto for creating/updating a Aanbieder and return the body for this
        $provider['address'] = $resource->getAddress();
        $provider['email'] = $resource->getEmail();
        $provider['phoneNumber'] = $resource->getPhoneNumber();
        $provider['name'] = $resource->getName();

        return $provider;
    }

    private function handleResult($provider)
    {
        $resource = new Provider();
        $resource->setAddress([]);
        $resource->setEmail($provider['email']);
        $resource->setPhoneNumber($provider['phoneNumber']);
        $resource->setName($provider['name']);
        $this->entityManager->persist($resource);
        return $resource;
    }
}
