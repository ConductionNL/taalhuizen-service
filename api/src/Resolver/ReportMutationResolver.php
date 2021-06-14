<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LanguageHouse;
use App\Entity\Report;
use App\Service\EDUService;
use App\Service\LearningNeedService;
use App\Service\MrcService;
use App\Service\ParticipationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
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

    public function __construct(
        CommonGroundService $commonGroundService,
        EntityManagerInterface $entityManager,
        MrcService $mrcService,
        SerializerInterface $serializer
    ) {
        $this->commonGroundService = $commonGroundService;
        $this->entityManager = $entityManager;
        $this->eduService = new EDUService($commonGroundService);
        $this->mrcService = $mrcService;
        $this->learningNeedService = new LearningNeedService($entityManager, $commonGroundService, new ParticipationService($entityManager, $commonGroundService, $mrcService));
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

    /**
     * Cleans an employee resource.
     *
     * @param array $employee The employee resource to clean
     *
     * @return array The resulting employee
     */
    public function cleanEmployee(array $employee): array
    {
        $result = $this->commonGroundService->getResource($employee['person'], ['fields=givenName,additionalName,lastName,emails,phones']);

        return array_merge($result, ['dateCreated'   => $employee['dateCreated']]);
    }

    /**
     * Cleans a collection of employees.
     *
     * @param ArrayCollection $employees The employees to clean
     *
     * @return array The cleaned employees
     */
    public function cleanEmployees(ArrayCollection $employees): array
    {
        $results = [];
        foreach ($employees as $employee) {
            $results[] = $this->cleanEmployee($employee);
        }

        return $results;
    }

    /**
     * Cleans a participant.
     *
     * @param array $participant The participant to check
     *
     * @return array The cleaned participant
     */
    public function cleanParticipant(array $participant): array
    {
        foreach ($participant as $key=>$value) {
            if (strpos($key, '@') !== false) {
                unset($participant[$key]);
            }
        }

        return $participant;
    }

    /**
     * Clean multiple participants.
     *
     * @param array $participants The participants to clean
     *
     * @return array The cleaned participants
     */
    public function cleanParticipants(array $participants): array
    {
        $results = [];
        foreach ($participants as $participant) {
            $results[] = $this->cleanParticipant($participant);
        }

        return $results;
    }

    /**
     * Creates a participants report.
     *
     * @param array $reportArray The data to convert into a report
     *
     * @return Report The resulting report
     */
    public function downloadParticipantsReport(array $reportArray): Report
    {
        $report = new Report();
        $time = new DateTime();
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

    /**
     * Creates a volunteers report.
     *
     * @param array $reportArray The data to convert into a report
     *
     * @return Report The resulting report
     */
    public function downloadVolunteersReport(array $reportArray): Report
    {
        $report = new Report();
        $time = new DateTime();
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

    /**
     * Sets a program provider query.
     *
     * @param array  $reportArray The report data
     * @param Report $report      The report to update
     *
     * @return array The resulting query
     */
    public function setProgramProviderQuery(array $reportArray, Report $report): array
    {
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

        return $query;
    }

    /**
     * Creates a desired learning outcomes report.
     *
     * @param array $reportArray The report data
     *
     * @return Report The resulting report
     */
    public function downloadDesiredLearningOutcomesReport(array $reportArray): Report
    {
        $report = new Report();
        $time = new DateTime();
        $query = $this->setProgramProviderQuery($reportArray, $report);
        if (isset($reportArray['dateFrom'])) {
            $report->setDateFrom($reportArray['dateFrom']);
            $dateFrom = $reportArray['dateFrom'];
        }
        if (isset($reportArray['dateUntil'])) {
            $report->setDateUntil($reportArray['dateUntil']);
            // edu/participants created after this date will not have eav/learningNeeds created before this date
            $query['dateCreated[strictly_before]'] = $reportArray['dateUntil'];
            $dateUntil = $reportArray['dateUntil'];
        }
        // Get all participants for this languageHouse created before dateUntil
        $participants = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], array_merge(['limit' => 1000], $query))['hydra:member'];
        // Get all eav/learningNeeds with dateCreated in between given dates for each edu/participant
        $learningNeeds = $this->fillLearningNeeds($participants, $dateFrom, $dateUntil);
        $learningNeedsCollection = $this->fillLearningNeedsCollection($learningNeeds);
        $report->setBase64data(base64_encode($this->serializer->serialize($learningNeedsCollection, 'csv', ['attributes' => ['studentId', 'dateCreated', 'desiredOutComesGoal', 'desiredOutComesTopic', 'desiredOutComesTopicOther', 'desiredOutComesApplication', 'desiredOutComesApplicationOther', 'desiredOutComesLevel', 'desiredOutComesLevelOther']])));
        $report->setFilename("DesiredLearningOutComesReport-{$time->format('YmdHis')}.csv");

        $this->entityManager->persist($report);

        return $report;
    }

    /**
     * fills the learning needs.
     *
     * @param array       $participants The participants for the learning needs
     * @param string|null $dateFrom     The date from which the data starts
     * @param string|null $dateUntil    The date on which the data ends
     *
     * @return array The resulting data
     */
    public function fillLearningNeeds(array $participants, ?string $dateFrom, ?string $dateUntil): array
    {
        $learningNeeds = [];
        foreach ($participants as $participant) {
            $learningNeedsResult = $this->learningNeedService->getLearningNeeds($participant['id'], $dateFrom, $dateUntil);
            if (isset($learningNeedsResult['learningNeeds']) && count($learningNeedsResult['learningNeeds']) > 0) {
                $learningNeeds = array_merge($learningNeeds, $learningNeedsResult['learningNeeds']);
            }
        }

        return $learningNeeds;
    }

    /**
     * Fills the learning needs as an collection.
     *
     * @param array $learningNeeds The learning needs as array
     *
     * @return ArrayCollection The resulting collection
     */
    public function fillLearningNeedsCollection(array $learningNeeds)
    {
        $learningNeedsCollection = new ArrayCollection();
        foreach ($learningNeeds as $learningNeed) {
            if (!isset($learningNeed['errorMessage'])) {
                $resourceResult = $this->learningNeedService->handleResult($learningNeed, $this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]), true);
                $resourceResult->setId(Uuid::getFactory()->fromString($learningNeed['id']));
                $learningNeedsCollection->add($resourceResult);
            }
        }

        return $learningNeedsCollection;
    }

    /**
     * Creates a report.
     *
     * @param array $reportArray The report data
     *
     * @return Report The resulting report
     */
    public function createReport(array $reportArray): Report
    {
        $report = new Report();
        $this->entityManager->persist($report);

        return $report;
    }

    /**
     * Updates a report.
     *
     * @param array $input The input needed to create a report
     *
     * @return Report The resulting report
     */
    public function updateReport(array $input): Report
    {
        $id = explode('/', $input['id']);
        $report = new Report();

        $this->entityManager->persist($report);

        return $report;
    }

    /**
     * Deletes a report.
     *
     * @param array $report The data to delete the report
     *
     * @return Report|null The result of the delete action
     */
    public function deleteReport(array $report): ?Report
    {
        return null;
    }

    /**
     * Sets the dates for a report.
     *
     * @param Report $resource      The report to set the dates in
     * @param array  $resourceArray The data to extract the dates from
     *
     * @return bool Whether the operation has succeeded
     */
    public function setDate(Report $resource, array $resourceArray): bool
    {
        if (isset($resourceArray['dateFrom'])) {
            $resource->setDateFrom($resourceArray['dateFrom']);
            $query['dateCreated[strictly_after]'] = $resourceArray['dateFrom'];
        }
        if (isset($resourceArray['dateUntil'])) {
            $resource->setDateUntil($resourceArray['dateUntil']);
            $query['dateCreated[before]'] = $resourceArray['dateUntil'];
        }

        return true;
    }
}
