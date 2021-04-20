<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Participation;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ParticipationMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
//        var_dump($context['info']->operation->name->value);
//        var_dump($context['info']->variableValues);
//        var_dump(get_class($item));
        if (!$item instanceof Participation && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createParticipation':
                return $this->createParticipation($item);
            case 'updateParticipation':
                return $this->updateParticipation($context['info']->variableValues['input']);
            case 'removeParticipation':
                return $this->removeParticipation($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createParticipation(Participation $resource): Participation
    {
        $result['result'] = [];

        // If aanbiederId is set generate the url for it
        $aanbiederUrl = null;
        if ($resource->getAanbiederId()) {
            $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $resource->getAanbiederId()]);
        }


        // w.i.p.
//
//        // Transform DTO info to learningNeed body...
//        $learningNeed = $this->dtoToLearningNeed($resource);
//
//        // Do some checks and error handling
//        $result = array_merge($result, $this->learningNeedService->checkLearningNeedValues($learningNeed, $studentUrl));
//
//        if (!isset($result['errorMessage'])) {
//            // No errors so lets continue... to:
//            // Save LearningNeed and connect student/participant to it
//            $result = array_merge($result, $this->learningNeedService->saveLearningNeed($result['learningNeed'], $studentUrl));
//
//            // Now put together the expected result in $result['result'] for Lifely:
//            $resourceResult = $this->learningNeedService->handleResult($result['learningNeed'], $resource->getStudentId());
//            $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
//        return $resourceResult;
        $resourceResult = new Participation();
        $resourceResult->setId(Uuid::getFactory()->fromString('708361df-b1e6-472f-a6f6-780427059d98'));
        return $resourceResult;
    }

    public function updateParticipation(array $input): Participation
    {
//        $result['result'] = [];
//
//        // If learningNeedUrl or learningNeedId is set generate the id for it, needed for eav calls later
//        $learningNeedId = null;
//        if (isset($input['learningNeedUrl'])) {
//            $learningNeedId = $this->commonGroundService->getUuidFromUrl($input['learningNeedUrl']);
//        } else {
//            $learningNeedId = explode('/',$input['id']);
//            if (is_array($learningNeedId)) {
//                $learningNeedId = end($learningNeedId);
//            }
//        }
//
//        // Transform input info to learningNeed body...
//        $learningNeed = $this->inputToLearningNeed($input);
//
//        // Do some checks and error handling
//        $result = array_merge($result, $this->learningNeedService->checkLearningNeedValues($learningNeed, null, $learningNeedId));
//
//        if (!isset($result['errorMessage'])) {
//            // No errors so lets continue... to:
//            // Save LearningNeed and connect student/participant to it
//            $result = array_merge($result, $this->learningNeedService->saveLearningNeed($result['learningNeed'], null, $learningNeedId));
//
//            // Now put together the expected result in $result['result'] for Lifely:
//            $resourceResult = $this->learningNeedService->handleResult($result['learningNeed'], $input['studentId']);
//            $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
//        $this->entityManager->persist($resourceResult);
//        return $resourceResult;
        return new Participation();
    }

    public function removeParticipation(array $participation): ?Participation
    {
//        $result['result'] = [];
//
//        // If learningNeedUrl or learningNeedId is set generate the id for it, needed for eav calls later
//        $learningNeedId = null;
//        if (isset($learningNeed['learningNeedUrl'])) {
//            $learningNeedId = $this->commonGroundService->getUuidFromUrl($learningNeed['learningNeedUrl']);
//        } elseif (isset($learningNeed['id'])) {
//            $learningNeedId = explode('/',$learningNeed['id']);
//            if (is_array($learningNeedId)) {
//                $learningNeedId = end($learningNeedId);
//            }
//        } else {
//            throw new Exception('No learningNeedUrl or id was specified');
//        }
//
//        $result = array_merge($result, $this->learningNeedService->deleteLearningNeed($learningNeedId));
//
//        $result['result'] = False;
//        if (isset($result['learningNeed'])){
//            $result['result'] = True;
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
        return null;
    }

    private function dtoToParticipation(Participation $resource) {
        // Get all info from the dto for creating a Participation and return the body for this
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

    private function inputToParticipation(array $input) {
        // Get all info from the input array for updating a Participation and return the body for this
        $learningNeed['description'] = $input['learningNeedDescription'];
        $learningNeed['motivation'] = $input['learningNeedMotivation'];
        $learningNeed['goal'] = $input['desiredOutComesGoal'];
        $learningNeed['topic'] = $input['desiredOutComesTopic'];
        if (isset($input['desiredOutComesTopicOther'])) {
            $learningNeed['topicOther'] = $input['desiredOutComesTopicOther'];
        }
        $learningNeed['application'] = $input['desiredOutComesApplication'];
        if (isset($input['desiredOutComesApplicationOther'])) {
            $learningNeed['applicationOther'] = $input['desiredOutComesApplicationOther'];
        }
        $learningNeed['level'] = $input['desiredOutComesLevel'];
        if (isset($input['desiredOutComesLevelOther'])) {
            $learningNeed['levelOther'] = $input['desiredOutComesLevelOther'];
        }
        $learningNeed['desiredOffer'] = $input['offerDesiredOffer'];
        $learningNeed['advisedOffer'] = $input['offerAdvisedOffer'];
        $learningNeed['offerDifference'] = $input['offerDifference'];
        if (isset($input['offerDifferenceOther'])) {
            $learningNeed['offerDifferenceOther'] = $input['offerDifferenceOther'];
        }
        if (isset($input['offerEngagements'])) {
            $learningNeed['offerEngagements'] = $input['offerEngagements'];
        }
        return $learningNeed;
    }
}
