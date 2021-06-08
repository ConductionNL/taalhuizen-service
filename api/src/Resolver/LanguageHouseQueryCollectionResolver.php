<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Entity\LanguageHouse;
use App\Service\CCService;
use App\Service\LanguageHouseService;
use App\Service\UcService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Ramsey\Uuid\Uuid;

class LanguageHouseQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CCService $ccService;
    private UcService $ucService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CCService $ccService,
        UcService $ucService
    )
    {
        $this->entityManager = $entityManager;
        $this->ccService = $ccService;
        $this->ucService = $ucService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        switch ($context['info']->operation->name->value) {
            case 'languageHouses':
                $collection = $this->ccService->getOrganizations($type = 'Taalhuis');
                return $this->createPaginator($collection, $context['args']);
            case 'userRolesByLanguageHouses':
                $collection = $this->ucService->getUserRolesByOrganization(
                    key_exists('languageHouseId', $context['args']) ?
                        $context['args']['languageHouseId'] :
                        null, $type = 'Taalhuis'
                );
                return $this->createPaginator($collection, $context['args']);
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
}
