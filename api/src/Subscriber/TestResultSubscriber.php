<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Participation;
use App\Exception\BadRequestPathException;
use App\Service\EAVService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\NewTestResultsService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TestResultSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $eavService;
    private $testResultService;
    private SerializerService $serializerService;
    private ErrorSerializerService $errorSerializerService;

    public function __construct(CommongroundService $commonGroundService, EAVService $eavService, LayerService $layerService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
        $this->testResultService = new NewTestResultsService($layerService);
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['testResult', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function testResult(ViewEvent $event)
    {
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        try {
            switch ($route) {
                case 'api_test_results_post_collection':
                    $response = $this->testResultService->createTestResult($resource);
                    break;
                default:
                    return;
            }

            if ($response instanceof Response) {
                $event->setResponse($response);
            }
            $this->serializerService->setResponse($response, $event);
        } catch (BadRequestPathException $exception) {
            $this->errorSerializerService->serialize($exception, $event);
        }

//        // this: is only here to make sure result has a result and that this is always shown first in the response body
//        $result['result'] = [];
//
//        // Handle a post collection
//        if ($route == 'api_participations_post_collection' and $resource instanceof Participation) {
//            // If aanbiederId is set generate the url for it
//            $aanbiederUrl = null;
//            if ($resource->getAanbiederId()) {
//                $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $resource->getAanbiederId()]);
//            }
//            // If learningNeedUrl or learningNeedId is set generate the url and id for it, needed for eav calls later
//            $learningNeedId = null;
//            if ($resource->getLearningNeedUrl()) {
//                $learningNeedUrl = $resource->getLearningNeedUrl();
//                $learningNeedId = $this->commonGroundService->getUuidFromUrl($learningNeedUrl);
//            } elseif ($resource->getLearningNeedId()) {
//                $learningNeedId = $resource->getLearningNeedId();
//            }
//
//            // Do some checks and error handling
//            $result = array_merge($result, $this->checkDtoValues($resource, $aanbiederUrl, $learningNeedId));
//
//            if (!isset($result['errorMessage'])) {
//                // No errors so lets continue... to: get all DTO info...
//                $participation = $this->dtoToParticipation($resource);
//
//                // ...and save this in the correct places
//                // Save Participation and connect aanbieder/organization to it
////                $result = array_merge($result, $this->saveParticipation($participation, $aanbiederUrl, $learningNeedId));
//
//                // Now put together the expected result in $result['result'] for Lifely:
////                $result['result'] = $this->handleResult($result['learningNeed']);
//                $result['result'] = $participation;
//            }
//        } elseif ($route == 'api_participations_get_participation_collection') {
//            // Handle a get collection for a specific item: /participations/{id}
//            $result['result'] = $route;
//        } elseif ($route == 'api_participations_get_collection') {
//            // Handle a get collection
//            $result['result'] = $route;
//        } elseif ($route == 'api_participations_delete_participation_collection') {
//            // Handle a delete (get collection for a specific item): /learning_needs/{id}/delete
//            $result['result'] = $route;
//        }
//
//        // If any error was caught set $result['result'] to null
//        if (isset($result['errorMessage'])) {
//            $result['result'] = null;
//        }
//
//        // Create the response
//        $response = new Response(
//            json_encode($result),
//            Response::HTTP_OK,
//            ['content-type' => 'application/json']
//        );
//        $event->setResponse($response);
    }

    private function checkDtoValues(Participation $resource, $aanbiederUrl, $learningNeedId)
    {
        $result = [];
        if ($resource->getOutComesTopic() == 'OTHER' && !$resource->getOutComesTopicOther()) {
            $result['errorMessage'] = 'Invalid request, outComesTopicOther is not set!';
        } elseif ($resource->getOutComesApplication() == 'OTHER' && !$resource->getOutComesApplicationOther()) {
            $result['errorMessage'] = 'Invalid request, outComesApplicationOther is not set!';
        } elseif ($resource->getOutComesLevel() == 'OTHER' && !$resource->getOutComesLevelOther()) {
            $result['errorMessage'] = 'Invalid request, outComesLevelOther is not set!';
        } elseif ($resource->getAanbiederId() and !$this->commonGroundService->isResource($aanbiederUrl)) {
            $result['errorMessage'] = 'Invalid request, aanbiederId is not an existing cc/organization!';
        } elseif (($resource->getLearningNeedId() || $resource->getLearningNeedUrl()) and !$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            $result['errorMessage'] = 'Invalid request, learningNeedId and/or learningNeedUrl is not an existing eav/learning_need!';
        }

        return $result;
    }

    private function dtoToParticipation(Participation $resource)
    {
        // Get all info from the dto for creating/updating a LearningNeed and return the body for this
        $participation['goal'] = $resource->getOutComesGoal();
        $participation['topic'] = $resource->getOutComesTopic();
        if ($resource->getOutComesTopicOther()) {
            $participation['topicOther'] = $resource->getOutComesTopicOther();
        }
        $participation['application'] = $resource->getOutComesApplication();
        if ($resource->getOutComesApplicationOther()) {
            $participation['applicationOther'] = $resource->getOutComesApplicationOther();
        }
        $participation['level'] = $resource->getOutComesLevel();
        if ($resource->getOutComesLevelOther()) {
            $participation['levelOther'] = $resource->getOutComesLevelOther();
        }
        if ($resource->getDetailsEngagements()) {
            $participation['offerEngagements'] = $resource->getDetailsEngagements();
        }

        return $participation;
    }
}
