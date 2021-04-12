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
            if ($resource->getDesiredOutComesTopic() == 'OTHER' && !$resource->getDesiredOutComesTopicOther()) {
                $result['error'] = 'Invalid request, desiredOutComesTopicOther is not set!';
            } elseif($resource->getDesiredOutComesApplication() == 'OTHER' && !$resource->getDesiredOutComesApplicationOther()) {
                $result['error'] = 'Invalid request, desiredOutComesApplicationOther is not set!';
            } elseif ($resource->getDesiredOutComesLevel() == 'OTHER' && !$resource->getDesiredOutComesLevelOther()) {
                $result['error'] = 'Invalid request, desiredOutComesLevelOther is not set!';
            } elseif ($resource->getOfferDifference() == 'YES_OTHER' && !$resource->getOfferDifferenceOther()) {
                $result['error'] = 'Invalid request, offerDifferenceOther is not set!';
            } else {
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
                // Save the learningNeed in EAV
                $result['result'] = $this->eavService->saveObject($learningNeed, 'learning_needs');

                // Save the participant in EAV with the learningNeed connected to it todo:
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
                $result['error'] = 'Please give a @eav or eavId query param!';
            }
        }

        if(isset($result['error'])) {
            $result['result'] = 'error';
        }

        $response = new Response(
            json_encode($result),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );

        $event->setResponse($response);
    }
}