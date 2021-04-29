<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Provider;
use App\Service\ProviderService;
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

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, ProviderService $providerService)
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
        switch ($context['info']->operation->name->value) {
            case 'createProvider':
                return $this->createProvider($item);
            case 'updateProvider':
                return $this->updateProvider($context['info']->variableValues['input']);
            case 'removeProvider':
                var_dump($context['info']->operation->name->value);
                return $this->removeProvider($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createProvider(Provider $resource): Provider
    {
        $result['result'] = [];

        // get all DTO info...
        $provider = $this->dtoToProvider($resource);

        $result = array_merge($result, $this->providerService->createProvider($provider));
        var_dump($result);

        // Now put together the expected result in $provider for Lifely:
        $resourceResult = $this->providerService->handleResult($provider);
        $resourceResult->setId(Uuid::getFactory()->fromString($result['provider']['id']));

        // If any error was catched throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $resourceResult;
    }

    public function updateProvider(array $input): Provider
    {
        $result['result'] = [];

        $providerId = explode('/', $input['id']);
        if (is_array($providerId)) {
            $providerId = end($providerId);
        }
        // Transform input info to Provider body...
        $provider = $this->inputToProvider($input);

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save Provider
            $result = array_merge($result, $this->providerService->updateProvider($provider, $providerId));
            var_dump($result);

            // Now put together the expected result in $provider for Lifely:
            $resourceResult = $this->providerService->handleResult($provider);
            var_dump($providerId);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['provider']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function removeProvider(array $input): ?Provider
    {
        $result['result'] = [];

        $id = explode('/', $input['id']);
        $id = end($id);
        $result = array_merge($result, $this->providerService->deleteProvider($id));

        $result['result'] = False;
        if (isset($result['learningNeed'])){
            $result['result'] = True;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return null;
    }

    private function dtoToProvider(Provider $resource)
    {
        // Get all info from the dto for creating/updating a Provider and return the body for this
        $provider['address'] = $resource->getAddress();
        $provider['email'] = $resource->getEmail();
        $provider['phoneNumber'] = $resource->getPhoneNumber();
        $provider['name'] = $resource->getName();

        return $provider;
    }


    private function inputToProvider(array $input)
    {
        // Get all info from the input array for updating a LearningNeed and return the body for this
        $provider['address'] = $input['address'];
        $provider['email'] = $input['email'];
        $provider['phoneNumber'] = $input['phoneNumber'];
        $provider['name'] = $input['name'];

        return $provider;
    }

}
