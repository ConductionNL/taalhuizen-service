<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LearningNeed;
use App\Entity\Provider;
use App\Entity\Taalhuis;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SensioLabs\Security\Exception\HttpException;

class ProviderMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
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
        switch($context['info']->operation->name->value){
            case 'createAanbieder':
                return $this->createAanbieder($item);
            case 'updateAanbieder':
                return $this->updateAanbieder($context['info']->variableValues['input']);
            case 'removeAanbieder':
                return $this->deleteAanbieder($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createAanbieder(Provider $resource): Provider
    {
        $result['result'] = [];

        // get all DTO info...
//        $learningNeed = $this->dtoToLearningNeed($resource);

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->handleResult($result['aanbieder']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['aanbieder']['id']));
        }

        // If any error was catched throw it
        if (isset($result['errorMessage'])) {
            throw new HttpException($result['errorMessage'], 400);
        }

        return $resourceResult;
    }

    public function updateAanbieder(array $input): Provider
    {
        $id = explode('/',$input['id']);
        $aanbieder = new Provider();
        $aanbieder->setId(Uuid::getFactory()->fromString(end($id)));
        $aanbieder->setEmail($input['email']);
        $aanbieder->setName($input['name']);

        $this->entityManager->persist($aanbieder);
        return $aanbieder;
    }

    public function deleteAanbieder(array $aanbieder): ?Provider
    {

        return null;
    }

    private function dtoToAanbieder(Provider $resource) {
        // Get all info from the dto for creating/updating a LearningNeed and return the body for this
        if ($resource->getAddress()) {
            $aanbieder['address'] = $resource->getAddress();
        }
        $aanbieder['email'] = $resource->getEmail();
        $aanbieder['phoneNumber'] = $resource->getPhoneNumber();
        $aanbieder['name'] = $resource->getName();

        return $aanbieder;
    }

    private function handleResult($aanbieder) {
        // TODO: when participation subscriber is done, also make sure to connect and return the participations of this learningNeed
        // TODO: add 'verwijzingen' in EAV to connect learningNeeds to participations¿
        // Put together the expected result for Lifely:
        $resource = new Provider();
        $resource->addAddress($aanbieder['address']);
        $resource->setEmail($aanbieder['email']);
        $resource->setPhoneNumber($aanbieder['phoneNumber']);
        $resource->setName($aanbieder['name']);
        $this->entityManager->persist($resource);
        return $resource;
    }
}
