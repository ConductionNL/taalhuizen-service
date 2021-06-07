<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
use App\Entity\Report;
use App\Service\EDUService;
use App\Service\LearningNeedService;
use App\Service\MrcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;

class ReportMutationResolver implements MutationResolverInterface
{
    private CommonGroundService $commonGroundService;
    private EntityManagerInterface $entityManager;
    private EDUService $eduService;
    private MrcService $mrcService;
    private LearningNeedService $learningNeedService;
    private SerializerInterface $serializer;

    public function __construct(CommonGroundService $commonGroundService, EntityManagerInterface $entityManager, EDUService $eduService, MrcService $mrcService, LearningNeedService $learningNeedService, SerializerInterface $serializer)
    {
        $this->commonGroundService = $commonGroundService;
        $this->entityManager = $entityManager;
        $this->eduService = $eduService;
        $this->mrcService = $mrcService;
        $this->learningNeedService = $learningNeedService;
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
        switch ($context['info']->operation->name->value) {
            case 'downloadParticipantsReport':
                return $this->downloadParticipantsReport($context['info']->variableValues['input']);
            case 'downloadVolunteersReport':
                return $this->downloadVolunteersReport($context['info']->variableValues['input']);
            case 'downloadDesiredLearningOutcomesReport':
                return $this->downloadDesiredLearningOutcomesReport($context['info']->variableValues['input']);
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

    public function cleanEmployee(array $employee): array
    {
        $result = $this->commonGroundService->getResource($employee['person'], ['fields=givenName,additionalName,lastName,emails,phones']);

        return array_merge($result, ['dateCreated'   => $employee['dateCreated']]);
    }

    public function cleanEmployees(ArrayCollection $employees): array
    {
        $results = [];
        foreach ($employees as $employee) {
            $results[] = $this->cleanEmployee($employee);
        }

        return $results;
    }

    public function cleanParticipant(array $participant): array
    {
        foreach ($participant as $key=>$value) {
            if (strpos($key, '@') !== false) {
                unset($participant[$key]);
            }
        }

        return $participant;
    }

    public function cleanParticipants(array $participants): array
    {
        $results = [];
        foreach ($participants as $participant) {
            $results[] = $this->cleanParticipant($participant);
        }

        return $results;
    }

    public function downloadParticipantsReport(array $reportArray): Report
    {
        $report = new Report();
        $time = new \DateTime();
        $query = [
            'extend' => 'person',
            'fields' => 'id,dateCreated,person.givenName,person.additionalName,person.familyName,person.emails,person.telephones',
        ];

        $this->setDate($report, $reportArray);

        if (isset($reportArray['languageHouseId'])) {
            $report->setLanguageHouseId($reportArray['languageHouseId']);
            $query['program.provider'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $reportArray['languageHouseId']]);
        } else {
            $languageHouseId = null;
        }
        $participants = $this->eduService->getParticipants($query);
        $participants = $this->cleanParticipants($participants);
        $report->setBase64data(base64_encode($this->serializer->serialize($participants, 'csv')));
        $report->setFilename("ParticipantsReport-{$time->format('YmdHis')}.csv");

        $this->entityManager->persist($report);

        return $report;
    }

    public function downloadVolunteersReport(array $reportArray): Report
    {
        $report = new Report();
        $time = new \DateTime();
        $query = [];

        $this->setDate($report, $reportArray);

        if (isset($reportArray['providerId'])) {
            $report->setProviderId($reportArray['providerId']);
            $providerId = $reportArray['providerId'];
        } else {
            $providerId = null;
        }
        $employees = $this->mrcService->getEmployees(null, $providerId, $query);

        $report->setBase64data(base64_encode($this->serializer->serialize($employees, 'csv', ['attributes' => ['givenName', 'additionalName', 'familyName', 'dateCreated', 'telephone', 'email']])));
        $report->setFilename("VolunteersReport-{$time->format('YmdHis')}.csv");

        $this->entityManager->persist($report);

        return $report;
    }

    public function downloadDesiredLearningOutcomesReport(array $reportArray): Report
    {
        $report = new Report();
        $time = new \DateTime();
        $query = [];
        if (isset($reportArray['languageHouseId'])) {
            $languageHouseId = explode('/', $reportArray['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
            $report->setLanguageHouseId($languageHouseId);
            $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
            $query['program.provider'] = $languageHouseUrl;
        }
        if (isset($reportArray['dateFrom'])) {
            $report->setDateFrom($reportArray['dateFrom']);
            $dateFrom = $reportArray['dateFrom'];
        } else {
            $dateFrom = null;
        }
        if (isset($reportArray['dateUntil'])) {
            $report->setDateUntil($reportArray['dateUntil']);
            // edu/participants created after this date will not have eav/learningNeeds created before this date
            $query['dateCreated[strictly_before]'] = $reportArray['dateUntil'];
            $dateUntil = $reportArray['dateUntil'];
        } else {
            $dateUntil = null;
        }
        // Get all participants for this languageHouse created before dateUntil
        $participants = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], array_merge(['limit' => 1000], $query))['hydra:member'];
        // Get all eav/learningNeeds with dateCreated in between given dates for each edu/participant
        $learningNeeds = [];
        foreach ($participants as $participant) {
            $learningNeedsResult = $this->learningNeedService->getLearningNeeds($participant['id'], $dateFrom, $dateUntil);
            if (isset($learningNeedsResult['learningNeeds']) && count($learningNeedsResult['learningNeeds']) > 0) {
                $learningNeeds = array_merge($learningNeeds, $learningNeedsResult['learningNeeds']);
            }
        }
        $learningNeedsCollection = new ArrayCollection();
        foreach ($learningNeeds as $learningNeed) {
            if (!isset($learningNeed['errorMessage'])) {
                $resourceResult = $this->learningNeedService->handleResult($learningNeed, $this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]), true);
                $resourceResult->setId(Uuid::getFactory()->fromString($learningNeed['id']));
                $learningNeedsCollection->add($resourceResult);
            }
        }
        $report->setBase64data(base64_encode($this->serializer->serialize($learningNeedsCollection, 'csv', ['attributes' => ['studentId', 'dateCreated', 'desiredOutComesGoal', 'desiredOutComesTopic', 'desiredOutComesTopicOther', 'desiredOutComesApplication', 'desiredOutComesApplicationOther', 'desiredOutComesLevel', 'desiredOutComesLevelOther']])));
        $report->setFilename("DesiredLearningOutComesReport-{$time->format('YmdHis')}.csv");

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
        $id = explode('/', $input['id']);
        $report = new Report();

        $this->entityManager->persist($report);

        return $report;
    }

    public function deleteReport(array $report): ?Report
    {
        return null;
    }

    public function setDate($resource, array $resourceArray): Report
    {
        if (isset($resourceArray['dateFrom'])) {
            $resource->setDateFrom($resourceArray['dateFrom']);
            $query['dateCreated[strictly_after]'] = $resourceArray['dateFrom'];
        } else {
            $dateFrom = null;
        }
        if (isset($resourceArray['dateUntil'])) {
            $resource->setDateUntil($resourceArray['dateUntil']);
            $query['dateCreated[before]'] = $resourceArray['dateUntil'];
        } else {
            $dateUntil = null;
        }
    }
}
