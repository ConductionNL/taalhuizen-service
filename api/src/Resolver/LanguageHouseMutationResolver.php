<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\LanguageHouse;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LanguageHouseMutationResolver implements MutationResolverInterface
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
        $languageHouse = new LanguageHouse();
        if(key_exists('name', $languageHouseArray)){
            $languageHouse->setName($languageHouseArray['name']);
        }
        if(key_exists('email', $languageHouseArray)){
            $languageHouse->setEmail($languageHouseArray['email']);
        }
        if(key_exists('address', $languageHouseArray)){
            $address = new Address();
            var_dump($languageHouseArray['address']);
            if(key_exists('street', $languageHouseArray['address'])) {
                $address->setStreet($languageHouseArray['address']['street']);
                echo 'boe!';
            }
            $this->entityManager->persist($address);
            $languageHouse->setAddress($address);
        }
//        var_dump($languageHouse->getAddress());
        $this->entityManager->persist($languageHouse);
        return $languageHouse;
    }

    public function updateLanguageHouse(array $input): LanguageHouse
    {
        $id = explode('/',$input['id']);
        $languageHouse = new LanguageHouse();
        $languageHouse->setId(Uuid::getFactory()->fromString(end($id)));
        $languageHouse->setEmail($input['email']);
        $languageHouse->setName($input['name']);

        $this->entityManager->persist($languageHouse);
        return $languageHouse;
    }

    public function deleteLanguageHouse(array $languageHouse): ?LanguageHouse
    {
        return null;
    }
}