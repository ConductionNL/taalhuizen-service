<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LearningNeed;
use App\Service\LearningNeedService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class LearningNeedMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private LearningNeedService $learningNeedService;

    /**
     * LearningNeedMutationResolver constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param CommonGroundService    $commonGroundService
     * @param LearningNeedService    $learningNeedService
     */
    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, LearningNeedService $learningNeedService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->learningNeedService = $learningNeedService;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof LearningNeed && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
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

    /**
     * Creates a new LearningNeed.
     *
     * @param LearningNeed $resource the input data for the new LearningNeed.
     *
     * @throws Exception
     *
     * @return LearningNeed the created LearningNeed.
     */
    public function createLearningNeed(LearningNeed $resource): LearningNeed
    {
        $result['result'] = [];

        // If studentId is set generate the url for it
        if ($resource->getStudentId()) {
            $studentId = explode('/', $resource->getStudentId());
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

    /**
     * This function gets the learningNeed id from the given input array.
     *
     * @param array $input the input data for a LearningNeed.
     *
     * @return string the id of the learningNeed.
     */
    public function handleLearningNeedId(array $input): string
    {
        if (isset($input['learningNeedUrl'])) {
            $learningNeedId = $this->commonGroundService->getUuidFromUrl($input['learningNeedUrl']);
        } else {
            $learningNeedId = explode('/', $input['id']);
            if (is_array($learningNeedId)) {
                $learningNeedId = end($learningNeedId);
            }
        }

        return $learningNeedId;
    }

    /**
     * Updates an existing LearningNeed.
     *
     * @param array $input the input data for a LearningNeed.
     *
     * @throws Exception
     *
     * @return LearningNeed the updated LearningNeed.
     */
    public function updateLearningNeed(array $input): LearningNeed
    {
        $result['result'] = [];

        // If learningNeedUrl or learningNeedId is set generate the id for it, needed for eav calls later
        $learningNeedId = null;
        $learningNeedId = $this->handleLearningNeedId($input);
        if (!isset($input['studentId'])) {
            $input['studentId'] = null;
        }

        // Transform input info to learningNeed body...
        $learningNeed = $this->inputToLearningNeed($input);

        // Do some checks and error handling
        $result = array_merge($result, $this->learningNeedService->checkLearningNeedValues($learningNeed, null, $learningNeedId));

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        // Save LearningNeed
        $result = array_merge($result, $this->learningNeedService->saveLearningNeed($result['learningNeed'], null, $learningNeedId));

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->learningNeedService->handleResult($result['learningNeed'], $input['studentId']);
        $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));

        $this->entityManager->persist($resourceResult);

        return $resourceResult;
    }

    /**
     * Deletes a LearningNeed.
     *
     * @param array $learningNeed the input data for a LearningNeed.
     *
     * @throws Exception
     *
     * @return LearningNeed|null null if successful.
     */
    public function removeLearningNeed(array $learningNeed): ?LearningNeed
    {
        $result['result'] = [];

        // If learningNeedUrl or learningNeedId is set generate the id for it, needed for eav calls later
        if (isset($learningNeed['learningNeedUrl'])) {
            $learningNeedId = $this->commonGroundService->getUuidFromUrl($learningNeed['learningNeedUrl']);
        } elseif (isset($learningNeed['id'])) {
            $learningNeedId = explode('/', $learningNeed['id']);
            if (is_array($learningNeedId)) {
                $learningNeedId = end($learningNeedId);
            }
        } else {
            throw new Exception('No learningNeedUrl or id was specified!');
        }

        $result = array_merge($result, $this->learningNeedService->deleteLearningNeed($learningNeedId));

        $result['result'] = false;
        if (isset($result['learningNeed'])) {
            $result['result'] = true;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return null;
    }

    /**
     * This function converts the input data for creating a LearningNeed from the DTO to a valid learningNeed body.
     *
     * @param LearningNeed $resource the LearningNeed DTO input.
     *
     * @return array a valid learningNeed body.
     */
    private function dtoToLearningNeed(LearningNeed $resource): array
    {
        // Get all info from the dto for creating a LearningNeed and return the body for this
        // note: everything that is nullabel in the dto has an if check, because eav doesn't like values set to null
        $learningNeed['description'] = $resource->getLearningNeedDescription();
        $learningNeed['motivation'] = $resource->getLearningNeedMotivation();
        $learningNeed['goal'] = $resource->getDesiredOutComesGoal();
        $learningNeed['topic'] = $resource->getDesiredOutComesTopic();
        $learningNeed['application'] = $resource->getDesiredOutComesApplication();
        $learningNeed['level'] = $resource->getDesiredOutComesLevel();
        $learningNeed = $this->handleOutcomeOthersDTO($resource, $learningNeed);
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

    /**
     * This function checks if the topic, application and level 'other' inputs are set when creating a new LearningNeed.
     * For each one that is set, it will be added to the learningNeed array.
     *
     * @param LearningNeed $resource     the LearningNeed DTO input.
     * @param array        $learningNeed the learningNeed array.
     *
     * @return array a valid learningNeed body containing the other options if they where set in the input.
     */
    public function handleOutcomeOthersDTO(LearningNeed $resource, array $learningNeed): array
    {
        if ($resource->getDesiredOutComesTopicOther()) {
            $learningNeed['topicOther'] = $resource->getDesiredOutComesTopicOther();
        }
        if ($resource->getDesiredOutComesApplicationOther()) {
            $learningNeed['applicationOther'] = $resource->getDesiredOutComesApplicationOther();
        }
        if ($resource->getDesiredOutComesLevelOther()) {
            $learningNeed['levelOther'] = $resource->getDesiredOutComesLevelOther();
        }

        return $learningNeed;
    }

    /**
     * This function checks if the topic, application and level 'other' inputs are set when updating a LearningNeed.
     * For each one that is set, it will be added to the learningNeed array.
     *
     * @param array $input        the input data for a LearningNeed.
     * @param array $learningNeed the learningNeed array.
     *
     * @return array a valid learningNeed body containing the other options if they where set in the input.
     */
    public function handleOutcomeOthers(array $input, array $learningNeed): array
    {
        if (isset($input['desiredOutComesTopicOther'])) {
            $learningNeed['topicOther'] = $input['desiredOutComesTopicOther'];
        }
        if (isset($input['desiredOutComesApplicationOther'])) {
            $learningNeed['applicationOther'] = $input['desiredOutComesApplicationOther'];
        }
        if (isset($input['desiredOutComesLevelOther'])) {
            $learningNeed['levelOther'] = $input['desiredOutComesLevelOther'];
        }

        return $learningNeed;
    }

    /**
     * This function converts the input data for updating a LearningNeed from the input array to a valid learningNeed body.
     *
     * @param array $input the input data for a LearningNeed.
     *
     * @return array a valid learningNeed body.
     */
    private function inputToLearningNeed(array $input): array
    {
        // Get all info from the input array for updating a LearningNeed and return the body for this
        $learningNeed['description'] = $input['learningNeedDescription'];
        $learningNeed['motivation'] = $input['learningNeedMotivation'];
        $learningNeed['goal'] = $input['desiredOutComesGoal'];
        $learningNeed['topic'] = $input['desiredOutComesTopic'];
        $learningNeed['level'] = $input['desiredOutComesLevel'];
        $learningNeed['application'] = $input['desiredOutComesApplication'];

        $learningNeed = $this->handleOutcomeOthers($input, $learningNeed);

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
