<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\LearningNeed;
use App\Exception\BadRequestPathException;
use App\Service\EAVService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\NewLearningNeedService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// TODO: REWRITE THIS FILE, this is (very) old code
class LearningNeedSubscriber implements EventSubscriberInterface
{
    private $commonGroundService;
    private $eavService;
    private $learningNeedsService;
    private SerializerService $serializerService;
    private ErrorSerializerService $errorSerializerService;



    public function __construct(CommongroundService $commonGroundService, EAVService $eavService, LayerService $layerService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
        $this->learningNeedsService = new NewLearningNeedService($layerService);
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);

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

        var_dump($route);
        die;
        // Lets limit the subscriber
        try {
            switch ($route) {
                case 'api_learning_needs_post_collection':
                    $response = $this->learningNeedsService->createLearningNeed($resource);
                    break;
                case 'api_learning_needs_get_collection':
                    $response = $this->getLearningNeeds($event->getRequest()->query->get('studentId'));
                    break;
                case 'api_learning_needs_get_item':
                    var_dump('yes');
                    die;
                    $response = $this->getLearningNeed($event->getRequest()->attributes->get('id'));
                    break;
//                case 'api_registrations_put_item':
//                    $response = $this->registrationService->updateRegistration($event->getRequest()->attributes->get('id'), json_decode($event->getRequest()->getContent(), true));
//                    break;
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
    }

    public function saveLearningNeed($learningNeed, $studentUrl = null, $learningNeedId = null)
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now = $now->format('d-m-Y H:i:s');

        // Save the learningNeed in EAV
        if (isset($learningNeedId)) {
            // Update
            $learningNeed['dateModified'] = $now;
            $learningNeed = $this->eavService->saveObject($learningNeed, ['entityName' => 'learning_needs', 'eavId' => $learningNeedId]);
        } else {
            // Create
            $learningNeed['dateCreated'] = $now;
            $learningNeed['dateModified'] = $now;
            $learningNeed = $this->eavService->saveObject($learningNeed, ['entityName' => 'learning_needs']);
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
            $getParticipant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
            $participant['learningNeeds'] = $getParticipant['learningNeeds'];
        } else {
            $participant['learningNeeds'] = [];
        }

        // Save the participant in EAV with the EAV/learningNeed connected to it
        if (!in_array($learningNeed['@eav'], $participant['learningNeeds'])) {
            array_push($participant['learningNeeds'], $learningNeed['@eav']);
            $participant = $this->eavService->saveObject($participant, ['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);

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
                $learningNeed = $this->eavService->saveObject($updateLearningNeed, ['entityName' => 'learning_needs', 'self' => $learningNeed['@eav']]);

                // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
                $result['learningNeed'] = $learningNeed;
            }
        }

        return $result;
    }

    public function getLearningNeed($id, $url = null)
    {
        $result = [];
        var_dump($id);
        die;
        // Get the learningNeed from EAV and add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
                $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'eavId' => $id]);
                $result['learningNeed'] = $learningNeed;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/learning_need!';
            }
        } elseif (isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'self' => $url]);
                $result['learningNeed'] = $learningNeed;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$url.' is not an existing eav/learning_need!';
            }
        }

        return new ArrayCollection($result);
    }

    public function getLearningNeeds($studentId)
    {
        // Get the eav/edu/participant learningNeeds from EAV and add the $learningNeeds @id's to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if ($this->eavService->hasEavObject(null, 'participants', $studentId, 'edu')) {
            $result['learningNeeds'] = [];
            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $studentId]);
            $participant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
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

        return new ArrayCollection($result);
    }

    public function deleteLearningNeed($id)
    {
        if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
            $result['participants'] = [];
            // Get the learningNeed from EAV
            $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'eavId' => $id]);

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
            $getParticipant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
            $participant['learningNeeds'] = array_values(array_filter($getParticipant['learningNeeds'], function ($participantLearningNeed) use ($learningNeedUrl) {
                return $participantLearningNeed != $learningNeedUrl;
            }));
            $result['participant'] = $this->eavService->saveObject($participant, ['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
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
