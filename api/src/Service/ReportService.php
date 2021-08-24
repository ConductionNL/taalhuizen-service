<?php

namespace App\Service;

use App\Entity\Report;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;

class ReportService
{
    private CCService $ccService;
    private EDUService $eduService;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private MrcService $mrcService;
    private UcService $ucService;
    private LearningNeedService $learningNeedService;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(LayerService $layerService, SerializerInterface $serializer, MrcService $mrcService, LearningNeedService $learningNeedService)
    {
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
        $this->commonGroundService = $layerService->commonGroundService;
        $this->ccService = new CCService($layerService);
        $this->eavService = new EAVService($this->commonGroundService);
        $this->mrcService = $mrcService;
        $this->learningNeedService = $learningNeedService;
        $this->entityManager = $layerService->entityManager;
        $this->serializer = $serializer;
    }

    /**
     * Creates a participants report.
     *
     * @param Report $report The data to convert into a report
     *
     * @return Report The resulting report
     */
    public function createParticipantsReport(Report $report): Report
    {
        $time = new DateTime();
        $query = [
            'extend' => 'person',
            'fields' => 'id,dateCreated,person.givenName,person.additionalName,person.familyName,person.emails,person.telephones',
        ];

        $this->setDate($report);

        if ($report->getOrganizationId()) {
            $query['program.organization'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $report->getOrganizationId()]);
        }

        $participants = $this->eduService->getParticipants($query);
        $participants = $this->cleanParticipants($participants);
        $report->setBase64(base64_encode($this->serializer->serialize($participants, 'csv')));
        $report->setFilename("ParticipantsReport-{$time->format('YmdHis')}.csv");

        $this->entityManager->persist($report);

        return $report;
    }

    /**
     * Creates a volunteers report.
     *
     * @param Report $report The data to convert into a report
     *
     * @throws Exception
     *
     * @return Report The resulting report
     */
    public function createVolunteersReport(Report $report): Report
    {
        $time = new DateTime();

        $this->setDate($report);

        if ($report->getOrganizationId()) {
            $query = ['organization' => $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $report->getOrganizationId()])];
        } else {
            $query = [];
        }

        $employees = $this->mrcService->getEmployees($query);

        $report->setBase64(base64_encode($this->serializer->serialize($employees, 'csv', ['attributes' => ['givenName', 'additionalName', 'familyName', 'dateCreated', 'telephone', 'email']])));
        $report->setFilename("VolunteersReport-{$time->format('YmdHis')}.csv");

        $this->entityManager->persist($report);

        return $report;
    }

    /**
     * Creates a desired learning outcomes report.
     *
     * @param Report $report The data to convert into a report
     *
     * @return Report The resulting report
     */
    public function createDesiredLearningOutcomesReport(Report $report): Report
    {
        $time = new DateTime();
        $query = $this->setProgramProviderQuery($report);

        if ($report->getDateFrom()) {
            $report->setDateFrom($report->getDateFrom());
            $dateFrom = $report->getDateFrom();
        }
        if ($report->getDateUntil()) {
            $report->setDateUntil($report->getDateUntil());
            // edu/participants created after this date will not have eav/learningNeeds created before this date
            $query['dateCreated[strictly_before]'] = $report->getDateUntil();
            $dateUntil = $report->getDateUntil();
        }

        // Get all participants for this languageHouse created before dateUntil
        $participants = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'participants'], array_merge(['limit' => 1000], $query))['hydra:member'];
        // Get all eav/learningNeeds with dateCreated in between given dates for each edu/participant
        $learningNeeds = $this->fillLearningNeeds($participants, $dateFrom, $dateUntil);
        $learningNeedsCollection = $this->fillLearningNeedsCollection($learningNeeds);
        $report->setBase64(base64_encode($this->serializer->serialize($learningNeedsCollection, 'csv', ['attributes' => ['studentId', 'dateCreated', 'desiredOutComesGoal', 'desiredOutComesTopic', 'desiredOutComesTopicOther', 'desiredOutComesApplication', 'desiredOutComesApplicationOther', 'desiredOutComesLevel', 'desiredOutComesLevelOther']])));
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
            $resourceResult = $this->learningNeedService->handleResult($learningNeed, $this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]), true);
            $resourceResult->setId(Uuid::getFactory()->fromString($learningNeed['id']));
            $learningNeedsCollection->add($resourceResult);
        }

        return $learningNeedsCollection;
    }

    /**
     * Sets a program provider query.
     *
     * @param Report $report The report to update
     *
     * @return array The resulting query
     */
    public function setProgramProviderQuery(Report $report): array
    {
        $query = [];
        if ($report->getOrganizationId()) {
            $organizationId = explode('/', $report->getOrganizationId());
            if (is_array($organizationId)) {
                $organizationId = end($organizationId);
            }
            $report->setOrganizationId($organizationId);
            $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $organizationId]);
            $query['program.provider'] = $organizationUrl;
        }

        return $query;
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
     * Cleans a participant.
     *
     * @param array $participant The participant to check
     *
     * @return array The cleaned participant
     */
    public function cleanParticipant(array $participant): array
    {
        foreach ($participant as $key => $value) {
            if (strpos($key, '@') !== false) {
                unset($participant[$key]);
            }
        }

        return $participant;
    }

    /**
     * Sets the dates for a report.
     *
     * @param Report $report The report to set the dates in
     *
     * @return bool Whether the operation has succeeded
     */
    public function setDate(Report $report): bool
    {
        if ($report->getDateFrom()) {
            $report->setDateFrom($report->getDateFrom());
            $query['dateCreated[strictly_after]'] = $report->getDateFrom();
        }
        if ($report->getDateUntil()) {
            $report->setDateUntil($report->getDateUntil());
            $query['dateCreated[before]'] = $report->getDateUntil();
        }

        return true;
    }
}
