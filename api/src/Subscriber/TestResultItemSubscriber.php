<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Service\EAVService;
use App\Service\LayerService;
use App\Service\NewTestResultsService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TestResultItemSubscriber implements EventSubscriberInterface
{
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private EAVService $eavService;
    private NewTestResultsService $testResultsService;

    /**
     * UserItemSubscriber constructor.
     *
     * @param LayerService $layerService
     * @param EAVService   $eavService
     */
    public function __construct(LayerService $layerService, EAVService $eavService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->eavService = $eavService;
        $this->testResultsService = new NewTestResultsService($layerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['testResult', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function testResult(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');

        // Lets limit the subscriber
        switch ($route) {
            case 'api_test_results_get_item':
                $response = $this->getTestResult($event->getRequest()->attributes->get('id'));
                break;
            case 'api_test_results_put_item':
                $resource = json_decode($event->getRequest()->getContent(), true);
                $response = $this->testResultsService->updateTestResult($resource, $event->getRequest()->attributes->get('id'));
                break;
            case 'api_test_results_delete_item':
                $response = $this->testResultsService->deleteTestResult($event->getRequest()->attributes->get('id'));
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

    public function getTestResult($id, $url = null)
    {
        $result = [];
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'results', $id)) {
                $testResult = $this->eavService->getObject(['entityName' => 'results', 'componentCode' => 'edu', 'eavId' => $id]);
                $result['testResult'] = $testResult;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/result!';
            }
        } elseif (isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $testResult = $this->eavService->getObject(['entityName' => 'results', 'componentCode' => 'edu', 'self' => $url]);
                $result['testResult'] = $testResult;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$url.' is not an existing eav/result!';
            }
        }

        return new ArrayCollection($result);
    }
}
