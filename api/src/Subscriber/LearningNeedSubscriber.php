<?php


namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\LearningNeed;
use App\Service\EAVService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

class LearningNeedSubscriber implements EventSubscriberInterface
{
    private $em;
    private $params;
    private $commonGroundService;
    private $eavService;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, CommongroundService $commonGroundService, EAVService $eavService)
    {
        $this->em = $em;
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['learningNeed', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function learningNeed(ViewEvent $event)
    {
        $method = $event->getRequest()->getMethod();
        $contentType = $event->getRequest()->headers->get('accept');
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        if($route != 'api_learning_needs_get_collection' && $route != 'api_learning_needs_post_collection'){
            return;
        }

        // Handle a post collection
        if($route == 'api_learning_needs_post_collection' and $resource instanceof LearningNeed){
            // this: is only here to make sure result is always shown first in the response body
            $result['result'] = [];

            // If studentId is set generate the url for it
            if ($resource->getStudentId()) {
                $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $resource->getStudentId()]);
            }
            // If learningNeedUrl or learningNeedId is set generate the url and id for it, needed for eav calls later
            if ($resource->getLearningNeedUrl()) {
                $learningNeedUrl = $resource->getLearningNeedUrl();
                $learningNeedId = $this->commonGroundService->getUuidFromUrl($learningNeedUrl);
                $learningNeedUrl = $this->commonGroundService->cleanUrl(['component' => 'eav', 'type' => 'object_entities', 'id' => $learningNeedId]);
            } elseif ($resource->getLearningNeedId()) {
                $learningNeedUrl = $this->commonGroundService->cleanUrl(['component' => 'eav', 'type' => 'object_entities', 'id' => $resource->getLearningNeedId()]);
                $learningNeedId = $resource->getLearningNeedId();
            }

            // Error handling could (and probably should) be put in one or more private functions in this subscriber
            // Do some checks and error handling
            if ($resource->getDesiredOutComesTopic() == 'OTHER' && !$resource->getDesiredOutComesTopicOther()) {
                $result['errorMessage'] = 'Invalid request, desiredOutComesTopicOther is not set!';
            } elseif($resource->getDesiredOutComesApplication() == 'OTHER' && !$resource->getDesiredOutComesApplicationOther()) {
                $result['errorMessage'] = 'Invalid request, desiredOutComesApplicationOther is not set!';
            } elseif ($resource->getDesiredOutComesLevel() == 'OTHER' && !$resource->getDesiredOutComesLevelOther()) {
                $result['errorMessage'] = 'Invalid request, desiredOutComesLevelOther is not set!';
            } elseif ($resource->getOfferDifference() == 'YES_OTHER' && !$resource->getOfferDifferenceOther()) {
                $result['errorMessage'] = 'Invalid request, offerDifferenceOther is not set!';
            } elseif ($resource->getStudentId() and !$this->commonGroundService->isResource($studentUrl)) {
                $result['errorMessage'] = 'Invalid request, studentId is not an existing edu/participant!';
            } elseif (($resource->getLearningNeedId() || $resource->getLearningNeedUrl()) and $this->eavService->hasEavObject($learningNeedUrl)) {
                $result['errorMessage'] = 'Invalid request, learningNeedId and/or learningNeedUrl is not an existing eav/objectEntity!';
            } else {
                // No errors so lets continue... to: get all DTO info and save this in the correct places
                $learningNeed = $this->dtoToLearningNeed($resource);

                // Save the learningNeed in EAV
                if (isset($learningNeedId)) {
                    // Update
                    // using learningNeedId instead of learningNeedUrl because of a bug in eav when updating a intern eav object with a @self
                    $learningNeed = $this->eavService->saveObject($learningNeed, 'learning_needs', 'eav', null, $learningNeedId);
                } else {
                    // Create
                    $learningNeed = $this->eavService->saveObject($learningNeed, 'learning_needs');
                }

                // Save the participant in EAV with the EAV/learningNeed connected to it
                if (isset($studentUrl)) {
                    if ($this->eavService->hasEavObject($studentUrl)) {
                        $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
                        $participant['learningNeeds'] = $getParticipant['learningNeeds'];
                    } else {
                        $participant['learningNeeds'] = [];
                    }
                    if (!in_array($learningNeed['@id'], $participant['learningNeeds'])) {
                        array_push($participant['learningNeeds'], $learningNeed['@id']);
                        $participant = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);

                        // Add $participant to the $result['participant'] because this is convenient when testing or debugging (mostly for us)
                        $result['participant'] = $participant;

                        // Update the learningNeed to add the EAV/edu/participant to it
                        if (isset($learningNeed['participants'])) {
                            $updateLearningNeed['participants'] = $learningNeed['participants'];
                        } else {
                            $updateLearningNeed['participants'] = [];
                        }
                        if (!in_array($participant['@id'], $updateLearningNeed['participants'])) {
                            array_push($updateLearningNeed['participants'], $participant['@id']);
                            $learningNeed = $this->eavService->saveObject($updateLearningNeed, 'learning_needs', 'eav', $learningNeed['@eav']);
                        }
                    }
                }

                // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
                $result['learningNeed'] = $learningNeed;
                // Now put together the expected result in $result['result'] for Lifely:
                $result['result'] = $this->handleResult($learningNeed);
            }
        } else {
            // Handle a get collection
            if ($event->getRequest()->query->get('@eav')) {
                // Get the learningNeed from EAV
                $result['result'] = $this->eavService->getObject('learning_needs', $event->getRequest()->query->get('@eav'));
            } elseif ($event->getRequest()->query->get('eavId')) {
                // Get the learningNeed from EAV
                $result['result'] = $this->eavService->getObject('learning_needs', null, 'eav', $event->getRequest()->query->get('eavId'));
            } else {
                $result['errorMessage'] = 'Please give a @eav or eavId query param!';
            }
        }

        // If any error was catched set $result['result'] to null
        if(isset($result['errorMessage'])) {
            $result['result'] = null;
        }

        // Create the response
        $response = new Response(
            json_encode($result),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );

        $event->setResponse($response);
    }

    private function dtoToLearningNeed($resource) {
        // Get all info from the dto for creating/updating a LearningNeed and return the body for this
        $learningNeed['description'] = $resource->getLearningNeedDescription();
        $learningNeed['motivation'] = $resource->getLearningNeedMotivation();
        $learningNeed['goal'] = $resource->getDesiredOutComesGoal();
        $learningNeed['topic'] = $resource->getDesiredOutComesTopic();
        if ($resource->getDesiredOutComesTopicOther()) {
            $learningNeed['topicOther'] = $resource->getDesiredOutComesTopicOther();
        }
        $learningNeed['application'] = $resource->getDesiredOutComesApplication();
        if ($resource->getDesiredOutComesApplicationOther()) {
            $learningNeed['applicationOther'] = $resource->getDesiredOutComesApplicationOther();
        }
        $learningNeed['level'] = $resource->getDesiredOutComesLevel();
        if ($resource->getDesiredOutComesLevelOther()) {
            $learningNeed['levelOther'] = $resource->getDesiredOutComesLevelOther();
        }
        $learningNeed['desiredOffer'] = $resource->getOfferDesiredOffer();
        $learningNeed['advisedOffer'] = $resource->getOfferAdvisedOffer();
        $learningNeed['offerDifference'] = $resource->getOfferDifference();
        if ($resource->getOfferDifferenceOther()) {
            $learningNeed['offerDifferenceOther'] = $resource->getOfferDifferenceOther();
        }
        if ($resource->getOfferEngagements()) {
            $learningNeed['offerEngagements'] = $resource->getOfferEngagements();
        }
        return $learningNeed;
    }

    private function handleResult($learningNeed) {
        // Put together the expected result for Lifely:
        return [
            'id' => $learningNeed['id'],
            'learningNeedDescription' => $learningNeed['description'],
            'learningNeedMotivation' => $learningNeed['motivation'],
            'desiredOutComesGoal' => $learningNeed['goal'],
            'desiredOutComesTopic' => $learningNeed['topic'],
            'desiredOutComesTopicOther' => $learningNeed['topicOther'],
            'desiredOutComesApplication' => $learningNeed['application'],
            'desiredOutComesApplicationOther' => $learningNeed['applicationOther'],
            'desiredOutComesLevel' => $learningNeed['level'],
            'desiredOutComesLevelOther' => $learningNeed['levelOther'],
            'offerDesiredOffer' => $learningNeed['desiredOffer'],
            'offerAdvisedOffer' => $learningNeed['advisedOffer'],
            'offerDifference' => $learningNeed['offerDifference'],
            'offerDifferenceOther' => $learningNeed['offerDifferenceOther'],
            'offerEngagements' => $learningNeed['offerEngagements'],
            'participations' => null,
        ];
    }
}
