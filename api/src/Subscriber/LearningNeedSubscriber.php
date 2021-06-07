<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\LearningNeed;
use App\Service\EAVService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
        $route = $event->getRequest()->attributes->get('_route');
        $resource = $event->getControllerResult();

        // Lets limit the subscriber
        if ($route != 'api_learning_needs_post_collection'
            && $route != 'api_learning_needs_get_learning_need_collection'
            && $route != 'api_learning_needs_get_collection'
            && $route != 'api_learning_needs_delete_learning_need_collection') {
            return;
        }

        // this: is only here to make sure result has a result and that this is always shown first in the response body
        $result['result'] = [];

        // Handle a post collection
        if ($route == 'api_learning_needs_post_collection' and $resource instanceof LearningNeed) {
            // If studentId is set generate the url for it
            $studentUrl = null;
            if ($resource->getStudentId()) {
                $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $resource->getStudentId()]);
            }
            // If learningNeedUrl or learningNeedId is set generate the url and id for it, needed for eav calls later
            $learningNeedId = null;
            if ($resource->getLearningNeedUrl()) {
                $learningNeedUrl = $resource->getLearningNeedUrl();
                $learningNeedId = $this->commonGroundService->getUuidFromUrl($learningNeedUrl);
            } elseif ($resource->getLearningNeedId()) {
                $learningNeedId = $resource->getLearningNeedId();
            }

            // Do some checks and error handling
            $result = array_merge($result, $this->checkDtoValues($resource, $studentUrl, $learningNeedId));

            if (!isset($result['errorMessage'])) {
                // No errors so lets continue... to: get all DTO info...
                $learningNeed = $this->dtoToLearningNeed($resource);

                // ...and save this in the correct places
                // Save LearningNeed and connect student/participant to it
                $result = array_merge($result, $this->saveLearningNeed($learningNeed, $studentUrl, $learningNeedId));

                // Now put together the expected result in $result['result'] for Lifely:
                $result['result'] = $this->handleResult($result['learningNeed']);
            }
        } elseif ($route == 'api_learning_needs_get_learning_need_collection') {
            // Handle a get collection for a specific item: /learning_needs/{id}
            $result = array_merge($result, $this->getLearningNeed($event->getRequest()->attributes->get('id')));

            // Now put together the expected result in $result['result'] for Lifely:
            if (isset($result['learningNeed'])) {
                $result['result'] = $this->handleResult($result['learningNeed']);
            }
        } elseif ($route == 'api_learning_needs_get_collection') {
            // Handle a get collection
            if ($event->getRequest()->query->get('learningNeedUrl')) {
                // Get the learningNeed from EAV
                $result = array_merge($result, $this->getLearningNeed(null, $event->getRequest()->query->get('learningNeedUrl')));
            } elseif ($event->getRequest()->query->get('learningNeedId')) {
                // Get the learningNeed from EAV
                $result = array_merge($result, $this->getLearningNeed($event->getRequest()->query->get('learningNeedId')));
            } elseif ($event->getRequest()->query->get('studentId')) {
                // Get the learningNeeds of this student from EAV
                $result = array_merge($result, $this->getLearningNeeds($event->getRequest()->query->get('studentId')));
            } else {
                $result['errorMessage'] = 'Please give a learningNeedUrl, learningNeedId or studentId query param!';
            }

            if (isset($result['learningNeed'])) {
                // Now put together the expected result in $result['result'] for Lifely:
                $result['result'] = $this->handleResult($result['learningNeed']);
            } elseif (isset($result['learningNeeds'])) {
                // Now put together the expected result in $result['result'] for Lifely:
                foreach ($result['learningNeeds'] as &$learningNeed) {
                    if (!isset($learningNeed['errorMessage'])) {
                        array_push($result['result'], $this->handleResult($learningNeed));
                        $learningNeed = $learningNeed['@id']; // Can be removed to show the entire body of all the learningNeeds
                    }
                }
            }
        } elseif ($route == 'api_learning_needs_delete_learning_need_collection') {
            // Handle a delete (get collection for a specific item): /learning_needs/{id}/delete
            $result = array_merge($result, $this->deleteLearningNeed($event->getRequest()->attributes->get('id')));

            $result['result'] = false;
            if (isset($result['learningNeed'])) {
                // Now put together the expected result in $result['result'] for Lifely:
                $result['result'] = true;
            }
        }

        // If any error was caught set $result['result'] to null
        if (isset($result['errorMessage'])) {
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

    public function saveLearningNeed($learningNeed, $studentUrl = null, $learningNeedId = null)
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now = $now->format('d-m-Y H:i:s');

        // Save the learningNeed in EAV
        if (isset($learningNeedId)) {
            // Update
            $learningNeed['dateModified'] = $now;
            $learningNeed = $this->eavService->saveObject($learningNeed, 'learning_needs', 'eav', null, $learningNeedId);
        } else {
            // Create
            $learningNeed['dateCreated'] = $now;
            $learningNeed['dateModified'] = $now;
            $learningNeed = $this->eavService->saveObject($learningNeed, 'learning_needs');
        }

        // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        $result['learningNeed'] = $learningNeed;

        // Save the participant in EAV with the EAV/learningNeed connected to it
        if (isset($studentUrl)) {
            $result = array_merge($result, $this->addStudentToLearningNeed($studentUrl, $learningNeed));
        }

        return $result;
    }

    public function addStudentToLearningNeed($studentUrl, $learningNeed)
    {
        $result = [];
        // Check if student already has an EAV object
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            $participant['learningNeeds'] = $getParticipant['learningNeeds'];
        } else {
            $participant['learningNeeds'] = [];
        }

        // Save the participant in EAV with the EAV/learningNeed connected to it
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

                // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
                $result['learningNeed'] = $learningNeed;
            }
        }

        return $result;
    }

    public function getLearningNeed($id, $url = null)
    {
        $result = [];
        // Get the learningNeed from EAV and add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
                $learningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $id);
                $result['learningNeed'] = $learningNeed;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/learning_need!';
            }
        } elseif (isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $learningNeed = $this->eavService->getObject('learning_needs', $url);
                $result['learningNeed'] = $learningNeed;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$url.' is not an existing eav/learning_need!';
            }
        }

        return $result;
    }

    public function getLearningNeeds($studentId)
    {
        // Get the eav/edu/participant learningNeeds from EAV and add the $learningNeeds @id's to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if ($this->eavService->hasEavObject(null, 'participants', $studentId, 'edu')) {
            $result['learningNeeds'] = [];
            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $studentId]);
            $participant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            foreach ($participant['learningNeeds'] as $learningNeedUrl) {
                $learningNeed = $this->getLearningNeed(null, $learningNeedUrl);
                if (isset($learningNeed['learningNeed'])) {
                    array_push($result['learningNeeds'], $learningNeed['learningNeed']);
                } else {
                    array_push($result['learningNeeds'], ['errorMessage' => $learningNeed['errorMessage']]);
                }
            }
        } else {
            $result['message'] = 'Warning, '.$studentId.' is not an existing eav/edu/participant!';
        }

        return $result;
    }

    public function deleteLearningNeed($id)
    {
        if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
            $result['participants'] = [];
            // Get the learningNeed from EAV
            $learningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $id);

            // Remove this learningNeed from all EAV/edu/participants
            foreach ($learningNeed['participants'] as $studentUrl) {
                $studentResult = $this->removeLearningNeedFromStudent($learningNeed['@eav'], $studentUrl);
                if (isset($studentResult['participant'])) {
                    // Add $studentUrl to the $result['participants'] because this is convenient when testing or debugging (mostly for us)
                    array_push($result['participants'], $studentResult['participant']['@id']);
                }
            }

            // Delete the learningNeed in EAV
            $this->eavService->deleteObject($learningNeed['eavId']);
            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['learningNeed'] = $learningNeed;
        } else {
            $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/learning_need!';
        }

        return $result;
    }

    public function removeLearningNeedFromStudent($learningNeedUrl, $studentUrl)
    {
        $result = [];
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            $participant['learningNeeds'] = array_values(array_filter($getParticipant['learningNeeds'], function ($participantLearningNeed) use ($learningNeedUrl) {
                return $participantLearningNeed != $learningNeedUrl;
            }));
            $result['participant'] = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);
        }

        return $result;
    }

    private function checkDtoValues(LearningNeed $resource, $studentUrl, $learningNeedId)
    {
        $result = [];
        if ($resource->getDesiredOutComesTopic() == 'OTHER' && !$resource->getDesiredOutComesTopicOther()) {
            $result['errorMessage'] = 'Invalid request, desiredOutComesTopicOther is not set!';
        } elseif ($resource->getDesiredOutComesApplication() == 'OTHER' && !$resource->getDesiredOutComesApplicationOther()) {
            $result['errorMessage'] = 'Invalid request, desiredOutComesApplicationOther is not set!';
        } elseif ($resource->getDesiredOutComesLevel() == 'OTHER' && !$resource->getDesiredOutComesLevelOther()) {
            $result['errorMessage'] = 'Invalid request, desiredOutComesLevelOther is not set!';
        } elseif ($resource->getOfferDifference() == 'YES_OTHER' && !$resource->getOfferDifferenceOther()) {
            $result['errorMessage'] = 'Invalid request, offerDifferenceOther is not set!';
        } elseif ($resource->getStudentId() and !$this->commonGroundService->isResource($studentUrl)) {
            $result['errorMessage'] = 'Invalid request, studentId is not an existing edu/participant!';
        } elseif (($resource->getLearningNeedId() || $resource->getLearningNeedUrl()) and !$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            $result['errorMessage'] = 'Invalid request, learningNeedId and/or learningNeedUrl is not an existing eav/learning_need!';
        }

        return $result;
    }

    private function dtoToLearningNeed(LearningNeed $resource)
    {
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

    private function handleResult($learningNeed)
    {
        // TODO: when participation subscriber is done, also make sure to connect and return the participations of this learningNeed
        // TODO: add 'verwijzingen' in EAV to connect learningNeeds to participations¿
        // Put together the expected result for Lifely:
        return [
            'id'                              => $learningNeed['id'],
            'learningNeedDescription'         => $learningNeed['description'],
            'learningNeedMotivation'          => $learningNeed['motivation'],
            'desiredOutComesGoal'             => $learningNeed['goal'],
            'desiredOutComesTopic'            => $learningNeed['topic'],
            'desiredOutComesTopicOther'       => $learningNeed['topicOther'],
            'desiredOutComesApplication'      => $learningNeed['application'],
            'desiredOutComesApplicationOther' => $learningNeed['applicationOther'],
            'desiredOutComesLevel'            => $learningNeed['level'],
            'desiredOutComesLevelOther'       => $learningNeed['levelOther'],
            'offerDesiredOffer'               => $learningNeed['desiredOffer'],
            'offerAdvisedOffer'               => $learningNeed['advisedOffer'],
            'offerDifference'                 => $learningNeed['offerDifference'],
            'offerDifferenceOther'            => $learningNeed['offerDifferenceOther'],
            'offerEngagements'                => $learningNeed['offerEngagements'],
            'participations'                  => null,
        ];
    }
}
