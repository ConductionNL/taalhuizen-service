<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Report;
use App\Entity\LanguageHouse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ReportMutationResolver implements MutationResolverInterface
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
        if (!$item instanceof Report && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createReport':
                return $this->createReport($context['info']->variableValues['input']);
            case 'updateReport':
                return $this->updateReport($context['info']->variableValues['input']);
            case 'removeReport':
                return $this->deleteReport($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createReport(array $reportArray): Report
    {
        $report = new Report();
        $this->entityManager->persist($report);
        return $report;
    }

    public function updateReport(array $input): Report
    {
        $id = explode('/',$input['id']);
        $report = new Report();


        $this->entityManager->persist($report);
        return $report;
    }

    public function deleteReport(array $report): ?Report
    {

        return null;
    }
}
