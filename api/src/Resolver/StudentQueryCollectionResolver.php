<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use App\Entity\Student;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Ramsey\Uuid\Uuid;

class StudentQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;

    public function __construct(CommongroundService $commonGroundService, StudentService $studentService){
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (!key_exists('languageHouseId', $context['args']) &&
            !key_exists('providerId', $context['args'])) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'students':
                return $this->createPaginator($this->students($context), $context['args']);
            case 'newRefferedStudents':
                return $this->createPaginator($this->newRefferedStudents($context), $context['args']);
            case 'activeStudents':
                return $this->createPaginator($this->activeStudents($context), $context['args']);
            case 'completedStudents':
                return $this->createPaginator($this->completedStudents($context), $context['args']);
            default:
                return $this->createPaginator(new ArrayCollection(), $context['args']);
        }
    }

    public function createPaginator(ArrayCollection $collection, array $args){
        if(key_exists('first', $args)){
            $maxItems = $args['first'];
            $firstItem = 0;
        } elseif(key_exists('last', $args)) {
            $maxItems = $args['last'];
            $firstItem = (count($collection) - 1) - $maxItems;
        } else {
            $maxItems = count($collection);
            $firstItem = 0;
        }
        if(key_exists('after', $args)){
            $firstItem = base64_decode($args['after']);
        } elseif(key_exists('before', $args)){
            $firstItem = base64_decode($args['before']) - $maxItems;
        }
        return new ArrayPaginator($collection->toArray(), $firstItem, $maxItems);
    }

    public function students(array $context): ?ArrayCollection
    {
        if(key_exists('languageHouseId', $context['args'])){
            $languageHouseId = explode('/',$context['args']['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
        } else {
            throw new Exception('The languageHouseId was not specified');
        }

        $students = $this->studentService->getStudents($languageHouseId);

        $collection = new ArrayCollection();
        // Now put together the expected result for Lifely:
        foreach ($students as $student) {
            if (isset($student['participant']['id'])) {
                $resourceResult = $this->studentService->handleResult($student['person'], $student['participant']);
                $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
                $collection->add($resourceResult);
            }
        }

        return $collection;
    }

    public function newRefferedStudents(array $context): ?ArrayCollection
    {
        $collection = new ArrayCollection();

        return $collection;
    }

    public function activeStudents(array $context): ?ArrayCollection
    {
        $collection = new ArrayCollection();

        return $collection;
    }

    public function completedStudents(array $context): ?ArrayCollection
    {
        $collection = new ArrayCollection();

        return $collection;
    }
}
