<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\LanguageHouse;
use App\Entity\Registration;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RegistrationMutationResolver implements MutationResolverInterface
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
        if (!$item instanceof Registration && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createRegistration':
                return $this->createRegistration($context['info']->variableValues['input']);
            case 'updateRegistration':
                return $this->updateRegistration($context['info']->variableValues['input']);
            case 'removeRegistration':
                return $this->deleteRegistration($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createRegistration(array $registrationArray): Registration
    {
        $registration = new Registration();

        $this->entityManager->persist($registration);
        return $registration;
    }

    public function updateRegistration(array $input): Registration
    {
        $id = explode('/',$input['id']);
        $languageHouse = new Registration();

        $this->entityManager->persist($languageHouse);
        return $languageHouse;
    }

    public function deleteRegistration(array $registration): ?LanguageHouse
    {
        return null;
    }
}
