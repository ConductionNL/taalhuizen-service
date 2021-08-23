<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Report;
use App\Service\EDUService;
use App\Service\LayerService;
use App\Service\ParticipationService;
use App\Service\ReportService;
use App\Service\UcService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
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
    private UcService $ucService;

    /**
     * StudentSubscriber constructor.
     *
     * @param ReportService $reportService
     * @param LayerService  $layerService
     * @param UcService     $ucService
     */
    public function __construct(ReportService $reportService, LayerService $layerService, UcService $ucService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->reportService = $reportService;
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
        $this->ucService = $ucService;
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
                if ($test = $this->checkAuthorization($event, $resource)) {
                    $event->setResponse($test);

                    return;
                }
                $response = $this->createParticipantsReport($resource);
                break;
            case 'api_reports_volunteers_report_collection':
                if ($test = $this->checkAuthorization($event, $resource)) {
                    $event->setResponse($test);

                    return;
                }
                $response = $this->createVolunteersReport($resource);
                break;
            case 'api_reports_desired_learning_outcomes_report_collection':
                if ($test = $this->checkAuthorization($event, $resource)) {
                    $event->setResponse($test);

                    return;
                }
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

    public function checkAuthorization(ViewEvent $event, object $report): ?Response
    {
        $token = str_replace('Bearer ', '', $event->getRequest()->headers->get('Authorization'));
        $payload = $this->ucService->validateJWTAndGetPayload($token, $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'public_key']));
        $currentUser = $this->ucService->getUserArray($payload['userId']);
        if (strpos($currentUser['organization'], $report->getOrganizationId()) === false) {
            return new Response(
                json_encode([
                    'message' => 'The wrong organizationId is given.',
                    'path'    => 'organizationId',
                    'data'    => ['organizationId' => $report->getOrganizationId()],
                ]),
                Response::HTTP_UNAUTHORIZED,
                ['content-type' => 'application/json']
            );
        }

        return null;
    }

    /**
     * @param object $report
     *
     * @return Report|Response
     */
    public function createParticipantsReport(object $report)
    {
        if ($report instanceof Report) {
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
        if ($report instanceof Report) {
            return $this->reportService->createParticipantsReport($report);
        } else {
            throw new Error('wrong organizationId');
        }
    }

    /**
     * @param object $report
     * @param array  $currentUser
     *
     * @return Report|Response
     */
    public function createDesiredLearningOutcomesReport(object $report): Report
    {
        if ($report instanceof Report) {
            return $this->reportService->createParticipantsReport($report);
        } else {
            throw new Error('wrong organizationId');
        }
    }
}
