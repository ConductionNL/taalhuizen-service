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
        $result['result'] = [];
        if(key_exists('languageHouseId', $context['args'])){
            $languageHouseId = $context['args']['languageHouseId'];
        } else {
            throw new Exception('The languageHouseId was not specified');
        }

        // Get the students from this taalhuis from EAV
        $result = array_merge($result, $this->studentService->getStudents($languageHouseId));

        $collection = new ArrayCollection();
        if (isset($result['students'])) {
            // Now put together the expected result for Lifely:
            foreach ($result['students'] as $student) {
                if (!isset($student['errorMessage'])) {
                    $resourceResult = $this->studentService->handleResult($student, $languageHouseId);
                    $resourceResult->setId(Uuid::getFactory()->fromString($student['id']));
                    $collection->add($resourceResult);
                    $student = $student['@id']; // Can be removed to show the entire body of all the students when dumping $result
                }
            }
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return $this->createPaginator($collection, $context['args']);

        if (!$item instanceof Student && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'activeStudent':
                return $this->activeStudents($context['info']->variableValues['input']);
            case 'newRefferedStudent':
                return $this->newRefferedStudents($context['info']->variableValues['input']);
            case 'completedStudent':
                return $this->completedStudents($context['info']->variableValues['input']);
            default:
                return $item;
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

    public function activeStudents(array $student): ?Student
    {

        return null;
    }

    public function newRefferedStudents(array $student): ?Student
    {

        return null;
    }



    public function completedStudents(array $student): ?Student
    {

        return null;
    }
}
