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

        // Lets limit the subscriber
        try {
            switch ($route) {
                case 'api_learning_needs_post_collection':
                    $response = $this->learningNeedsService->createLearningNeed($resource);
                    break;
                case 'api_learning_needs_get_collection':
                    $response = $this->getLearningNeeds($event->getRequest()->query->get('studentId'));
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
            $result['totalItems'] = count($result['learningNeeds']);
        } else {
            $result['message'] = 'Warning, '.$studentId.' is not an existing eav/edu/participant!';
        }

        return new ArrayCollection($result);
    }

}
