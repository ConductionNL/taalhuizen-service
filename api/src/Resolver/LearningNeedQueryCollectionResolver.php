<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\LearningNeedService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ramsey\Uuid\Uuid;
use SensioLabs\Security\Exception\HttpException;

class LearningNeedQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private LearningNeedService $learningNeedService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, LearningNeedService $learningNeedService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->learningNeedService = $learningNeedService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        $result['result'] = [];

        // Todo get studentId from somewhere
        $studentId = '4a976327-578f-485a-b485-b22a72ef38b0';

        // Get the learningNeeds of this student from EAV
        $result = array_merge($result, $this->learningNeedService->getLearningNeeds($studentId));

        $collection = new ArrayCollection();
        if (isset($result['learningNeeds'])) {
            // Now put together the expected result for Lifely:
            foreach ($result['learningNeeds'] as &$learningNeed) {
                if (!isset($learningNeed['errorMessage'])) {
                    $resourceResult = $this->learningNeedService->handleResult($learningNeed);
                    $resourceResult->setId(Uuid::getFactory()->fromString($learningNeed['id']));
                    $collection->add($resourceResult);
                    $learningNeed = $learningNeed['@id']; // Can be removed to show the entire body of all the learningNeeds when dumping $result
                }
            }
        }

        // If any error was catched throw it
        if (isset($result['errorMessage'])) {
            throw new HttpException($result['errorMessage'], 400);
        }

        $paginator = new ArrayPaginator($collection->toArray(), 0, count($collection));
        return $paginator;
    }
}
