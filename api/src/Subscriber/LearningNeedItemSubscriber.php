<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Registration;
use App\Exception\BadRequestPathException;
use App\Service\EAVService;
use App\Service\ErrorSerializerService;
use App\Service\LayerService;
use App\Service\NewLearningNeedService;
use App\Service\NewRegistrationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LearningNeedItemSubscriber implements EventSubscriberInterface
{
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private EAVService $eavService;
    private NewLearningNeedService $learningneedService;
    private ErrorSerializerService $errorSerializerService;

    /**
     * UserItemSubscriber constructor.
     *
     * @param LayerService           $layerService
     * @param EAVService             $eavService
     */
    public function __construct(LayerService $layerService, EAVService $eavService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->serializerService = new SerializerService($layerService->serializer);
        $this->eavService = $eavService;
        $this->learningneedService = new NewLearningNeedService($layerService);
        $this->errorSerializerService = new ErrorSerializerService($this->serializerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['learningNeed', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function learningNeed(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');

        try {
            switch ($route) {
                case 'api_learning_needs_get_item':
                    $response = $this->getLearningNeed($event->getRequest()->attributes->get('id'));
                    break;
                case 'api_learning_needs_put_item':
                    $resource = json_decode($event->getRequest()->getContent(), true);
                    $response = $this->learningneedService->updateLearningNeed($resource, $event->getRequest()->attributes->get('id'));
                    break;
                case 'api_learning_needs_delete_item':
                    $response = $this->learningneedService->deleteLearningNeed($event->getRequest()->attributes->get('id'));
                    break;
                default:
                    return;
            }
            if ($response instanceof Response) {
                $event->setResponse($response);
                
                return;
            }
            $this->serializerService->setResponse($response, $event);
        } catch (BadRequestPathException $exception) {
            $this->errorSerializerService->serialize($exception, $event);
        }

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


}
