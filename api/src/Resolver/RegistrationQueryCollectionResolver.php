<?php


namespace App\Resolver;


use ApiPlatform\Core\DataProvider\ArrayPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use ContainerB9GRdr1\getDebug_Security_UserValueResolverService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Ramsey\Uuid\Uuid;

class RegistrationQueryCollectionResolver implements QueryCollectionResolverInterface
{
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;

    public function __construct(CommongroundService $commonGroundService, StudentService $studentService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(iterable $collection, array $context): iterable
    {
        if (!key_exists('languageHouseId', $context['args'])) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'registrations':
                return $this->createPaginator($this->students($context), $context['args']);
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

    public function students(array $context): ?ArrayCollection
    {
        if (key_exists('languageHouseId', $context['args'])) {
            $languageHouseId = explode('/', $context['args']['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
        } else {
            throw new Exception('The languageHouseId was not specified');
        }

        $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        $query = ['program.provider' => $languageHouseUrl];

        $students = $this->studentService->getStudents($query);

        $collection = new ArrayCollection();
        // Now put together the expected result for Lifely:
        foreach ($students as $student) {
            if (isset($student['participant']['id']) && $student['participant']['referredBy']) {
                $organization = $this->commonGroundService->getResource($student['participant']['referredBy']);
                $registrarPerson = $this->commonGroundService->getResource($organization['persons'][0]['@id']);
                $memo = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['topic' => $student['person']['@id'], 'author' => $organization['@id']])["hydra:member"][0];

                $resourceResult = $this->studentService->handleResult($student['person'], $student['participant'], $registrarPerson, $organization, $memo, $registration = true);
                $resourceResult->setId(Uuid::getFactory()->fromString($student['participant']['id']));
                $collection->add($resourceResult);
            }
        }
        return $collection;
    }
}
