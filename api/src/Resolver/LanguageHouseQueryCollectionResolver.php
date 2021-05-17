<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Entity\LanguageHouse;
use App\Service\LanguageHouseService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Ramsey\Uuid\Uuid;

class LanguageHouseQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private LanguageHouseService $languageHouseService;
    private EntityManagerInterface $entityManager;

    public function __construct(LanguageHouseService $languageHouseService, EntityManagerInterface $entityManager)
    {
        $this->languageHouseService = $languageHouseService;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        switch ($context['info']->operation->name->value) {
            case 'languageHouses':
                return $this->createPaginator($this->languageHouses($context), $context['args']);
            case 'userRolesByLanguageHouses':
                return $this->createPaginator($this->userRolesByLanguageHouses($context), $context['args']);
            default:
                return $this->createPaginator(new ArrayCollection(), $context['args']);
        }
    }

    public function createPaginator(ArrayCollection $collection, array $args)
    {
        if (key_exists('first', $args)) {
            $maxItems = $args['first'];
            $firstItem = 0;
        } elseif (key_exists('last', $args)) {
            $maxItems = $args['last'];
            $firstItem = (count($collection) - 1) - $maxItems;
        } else {
            $maxItems = count($collection);
            $firstItem = 0;
        }
        if (key_exists('after', $args)) {
            $firstItem = base64_decode($args['after']);
        } elseif (key_exists('before', $args)) {
            $firstItem = base64_decode($args['before']) - $maxItems;
        }
        return new ArrayPaginator($collection->toArray(), $firstItem, $maxItems);
    }

    public function languageHouses(?array $context): ?ArrayCollection
    {
        // Get the languageHouses
        $result = $this->languageHouseService->getLanguageHouses();

        $collection = new ArrayCollection();
        foreach ($result['languageHouses'] as $languageHouse) {
            $resourceResult = $this->languageHouseService->handleResult($languageHouse);
            $resourceResult->setId(Uuid::getFactory()->fromString($languageHouse['id']));
            $collection->add($resourceResult);
        }
        return $collection;
    }

    public function userRolesByLanguageHouses(array $context): ?ArrayCollection
    {
        if(key_exists('languageHouseId', $context['args'])){
            $languageHouseId = explode('/',$context['args']['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
        } else {
            throw new Exception('The languageHouseId was not specified');
        }

        $userRoles = $this->languageHouseService->getUserRolesByLanguageHouse($languageHouseId);

        $collection = new ArrayCollection();
        foreach ($userRoles as $userRole) {
            $resourceResult = $this->languageHouseService->handleResult(null, $userRole);
            $resourceResult->setId(Uuid::getFactory()->fromString($userRole['id']));
            $collection->add($resourceResult);
        }
        return $collection;
    }
}
