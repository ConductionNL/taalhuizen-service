<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Report;
use App\Entity\LanguageHouse;
use App\Entity\User;
use App\Service\EDUService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ReportMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private EDUService $eduService;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, EDUService $eduService, SerializerInterface $serializer){
        $this->entityManager = $entityManager;
        $this->eduService = $eduService;
        $this->serializer = $serializer;
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
            case 'downloadParticipantsReport':
                return $this->downloadParticipantsReport($context['info']->variableValues['input']);
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

    public function cleanParticipants(array $participants, bool $names): array
    {
        $results = [];
        foreach($participants as $participant){
            $result = ['id' => $participant['id'], 'dateCreated' => $participant['dateCreated']];
            if($names){
                $result['givenName'] = key_exists('givenName', $participant['person']) ? $participant['person']['givenName'] : null;
                $result['additionalName'] = key_exists('additionalName', $participant['person']) ? $participant['person']['additionalName'] : null;
                $result['lastName'] = key_exists('lastName', $participant['person']) ? $result['lastName'] = $participant['person']['lastName'] : null;
            }
            $result['email'] = key_exists('emails', $participant['person']) && count($participant['person']['emails']) > 0 ? $participant['person']['emails'][0]['email'] : null;
            $result['telephone'] = key_exists('telephones', $participant['person']) && count($participant['person']['telephones']) > 0 ? $participant['person']['telephones'][0]['telephone'] : null;
            $results[] = $result;
        }
        return $results;
    }

    public function downloadParticipantsReport(array $reportArray): Report
    {
        $report = new Report();
        $time = new \DateTime();
        if(isset($reportArray['dateFrom'])){
            $report->setDateFrom($reportArray['dateFrom']);
            $dateFrom = $reportArray['dateFrom'];
        } else {
            $dateFrom = null;
        }
        if(isset($reportArray['dateUntil'])){
            $report->setDateUntil($reportArray['dateUntil']);
            $dateUntil = $reportArray['dateUntil'];
        } else {
            $dateUntil = null;
        }
        if(isset($reportArray['languageHouseId'])){
            $report->setLanguageHouseId($reportArray['languageHouseId']);
            $languageHouseId = $reportArray['languageHouseId'];
        } else {
            $languageHouseId = null;
        }
        $participants = $this->eduService->getParticipants($languageHouseId, $dateFrom, $dateUntil);
        $participants = $this->cleanParticipants($participants, true);
        $report->setBase64data(base64_encode($this->serializer->serialize($participants, 'csv', [])));
        $report->setFilename("ParticipantsReport-{$time->format('YmdHis')}.csv");

        $this->entityManager->persist($report);

        return $report;
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
