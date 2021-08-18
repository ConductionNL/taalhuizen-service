<?php

namespace App\Subscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Registration;
use App\Service\EAVService;
use App\Service\LayerService;
use App\Service\NewLearningNeedService;
use App\Service\NewParticipationService;
use App\Service\NewRegistrationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\CommonGroundBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ParticipationItemSubscriber implements EventSubscriberInterface
{
    private CommonGroundService $commonGroundService;
    private SerializerService $serializerService;
    private EAVService $eavService;
    private NewParticipationService $participationService;

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
        $this->participationService = new NewParticipationService($layerService);
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['participation', EventPriorities::PRE_DESERIALIZE],
        ];
    }

    /**
     * @param RequestEvent $event
     *
     * @throws Exception
     */
    public function participation(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $route = $event->getRequest()->attributes->get('_route');

        // Lets limit the subscriber
        switch ($route) {
            case 'api_participations_get_item':
                $response = $this->getParticipation($event->getRequest()->attributes->get('id'));
                break;
            case 'api_participations_put_item':
                $resource = json_decode($event->getRequest()->getContent(), true);
                $response = $this->participationService->updateParticipation($resource, $event->getRequest()->attributes->get('id'));
                break;
            case 'api_participations_delete_item':
                $response = $this->participationService->deleteParticipation($event->getRequest()->attributes->get('id'));
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

    public function getParticipation($id, $url = null)
    {
        $result = [];
        // Get the learningNeed from EAV and add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'participations', $id)) {
                $participation = $this->eavService->getObject(['entityName' => 'participations', 'eavId' => $id]);
                $result['participation'] = $participation;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/participation!';
            }
        } elseif (isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $participation = $this->eavService->getObject(['entityName' => 'participations', 'self' => $url]);
                $result['participation'] = $participation;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$url.' is not an existing eav/participation!';
            }
        }

        return new ArrayCollection($result);
    }


}
