<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Registration;
use App\Entity\Report;
use App\Entity\Student;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\NewRegistrationService;
use App\Service\ReportService;
use App\Service\StudentService;
use App\Service\ParticipationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use function GuzzleHttp\json_decode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ReportSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private ReportService $reportService;
    private EDUService $eduService;

    /**
     * StudentSubscriber constructor.
     *
     * @param ReportService   $reportService
     * @param LayerService $layerService
     */
    public function __construct(ReportService $reportService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->reportService = $reportService;
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
//        $this->participationService = new ParticipationService($studentService, $layerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['report', EventPriorities::PRE_VALIDATE],
        ];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     */
    public function report(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();
        
        // Lets limit the subscriber
        switch ($route) {
            case 'api_reports_participants_report_collection':
                $response = $this->createParticipantsReport($resource);
                break;
            case 'api_reports_volunteers_report_collection':
                $response = $this->createVolunteersReport($resource);
                break;
            case 'api_reports_desired_learning_outcomes_report_collection':
                $response = $this->createDesiredLearningOutcomesReport($resource);
                break;
            default:
                return;
        }

        if ($response instanceof Response) {
            $event->setResponse($response);

            return;
        }
        $this->serializerService->setResponse($response, $event);
    }

    /**
     * @param object $report
     *
     * @return Report|Response
     */
    public function createParticipantsReport(object $report)
    {
        $query['program.provider'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $report->getOrganizationId()]);
        if (!$this->eduService->getParticipants($query)) {
            return new Response(
                json_encode([
                    'message' => 'The wrong organizationId is given.',
                    'path'    => 'organizationId',
                    'data'    => $report->getOrganizationId(),
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }

        if($report instanceof Report){
            return $this->reportService->createParticipantsReport($report);
        } else {
            throw new Error('wrong organizationId');
        }
    }

    /**
     * @param object $report
     *
     * @return Report|Response
     */
    public function createVolunteersReport(object $report): Report
    {
        $query['program.provider'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $report->getOrganizationId()]);
        if (!$this->eduService->getParticipants($query)) {
            return new Response(
                json_encode([
                    'message' => 'The wrong organizationId is given.',
                    'path'    => 'organizationId',
                    'data'    => $report->getOrganizationId(),
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }

        if($report instanceof Report){
            return $this->reportService->createParticipantsReport($report);
        } else {
            throw new Error('wrong organizationId');
        }
    }

    /**
     * @param object $report
     *
     * @return Report|Response
     */
    public function createDesiredLearningOutcomesReport(object $report): Report
    {
        $query['program.provider'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $report->getOrganizationId()]);
        if (!$this->eduService->getParticipants($query)) {
            return new Response(
                json_encode([
                    'message' => 'The wrong organizationId is given.',
                    'path'    => 'organizationId',
                    'data'    => $report->getOrganizationId(),
                ]),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }

        if($report instanceof Report){
            return $this->reportService->createParticipantsReport($report);
        } else {
            throw new Error('wrong organizationId');
        }
    }
}
