<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LearningNeed;
use App\Service\LearningNeedService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class LearningNeedMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private LearningNeedService $learningNeedService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, LearningNeedService $learningNeedService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->learningNeedService = $learningNeedService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof LearningNeed && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createLearningNeed':
                return $this->createLearningNeed($item);
            case 'updateLearningNeed':
                return $this->updateLearningNeed($context['info']->variableValues['input']);
            case 'removeLearningNeed':
                return $this->removeLearningNeed($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createLearningNeed(LearningNeed $resource): LearningNeed
    {
        $result['result'] = [];

        // If studentId is set generate the url for it
        if ($resource->getStudentId()) {
            $studentId = explode('/',$resource->getStudentId());
            if (is_array($studentId)) {
                $studentId = end($studentId);
            }
            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $studentId]);
        } else {
            throw new Exception('Invalid request, studentId is not set!');
        }

        // Transform DTO info to learningNeed body...
        $learningNeed = $this->dtoToLearningNeed($resource);

        // Do some checks and error handling
        $result = array_merge($result, $this->learningNeedService->checkLearningNeedValues($learningNeed, $studentUrl));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save LearningNeed and connect student/participant to it
            $result = array_merge($result, $this->learningNeedService->saveLearningNeed($result['learningNeed'], $studentUrl));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->learningNeedService->handleResult($result['learningNeed'], $resource->getStudentId());
            $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        return $resourceResult;
    }

    public function updateLearningNeed(array $input): LearningNeed
    {
        $result['result'] = [];

        // If learningNeedUrl or learningNeedId is set generate the id for it, needed for eav calls later
        $learningNeedId = null;
        if (isset($input['learningNeedUrl'])) {
            $learningNeedId = $this->commonGroundService->getUuidFromUrl($input['learningNeedUrl']);
        } else {
            $learningNeedId = explode('/',$input['id']);
            if (is_array($learningNeedId)) {
                $learningNeedId = end($learningNeedId);
            }
        }
        if (!isset($input['studentId'])) {
            $input['studentId'] = null;
        }

        // Transform input info to learningNeed body...
        $learningNeed = $this->inputToLearningNeed($input);

        // Do some checks and error handling
        $result = array_merge($result, $this->learningNeedService->checkLearningNeedValues($learningNeed, null, $learningNeedId));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save LearningNeed
            $result = array_merge($result, $this->learningNeedService->saveLearningNeed($result['learningNeed'], null, $learningNeedId));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->learningNeedService->handleResult($result['learningNeed'], $input['studentId']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function removeLearningNeed(array $learningNeed): ?LearningNeed
    {
        $result['result'] = [];

        // If learningNeedUrl or learningNeedId is set generate the id for it, needed for eav calls later
        if (isset($learningNeed['learningNeedUrl'])) {
            $learningNeedId = $this->commonGroundService->getUuidFromUrl($learningNeed['learningNeedUrl']);
        } elseif (isset($learningNeed['id'])) {
            $learningNeedId = explode('/',$learningNeed['id']);
            if (is_array($learningNeedId)) {
                $learningNeedId = end($learningNeedId);
            }
        } else {
            throw new Exception('No learningNeedUrl or id was specified!');
        }

        $result = array_merge($result, $this->learningNeedService->deleteLearningNeed($learningNeedId));

        $result['result'] = False;
        if (isset($result['learningNeed'])){
            $result['result'] = True;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        return null;
    }

    private function     dtoToLearningNeed(LearningNeed $resource) {
        // Get all info from the dto for creating a LearningNeed and return the body for this
        // note: everything that is nullabel in the dto has an if check, because eav doesn't like values set to null
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

    private function inputToLearningNeed(array $input)
    {
        // Get all info from the input array for updating a LearningNeed and return the body for this
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
