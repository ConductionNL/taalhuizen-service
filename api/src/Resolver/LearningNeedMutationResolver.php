<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\LearningNeed;
use App\Service\EAVService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SensioLabs\Security\Exception\HttpException;

class LearningNeedMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, EAVService $eavService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
//        var_dump($context['info']->operation->name->value);
//        var_dump($context['info']->variableValues);
//        var_dump(get_class($item));
        if (!$item instanceof LearningNeed && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createLearningNeed':
                return $this->createLearningNeed($item);
            case 'updateLearningNeed':
                return $this->updateLearningNeed($context['info']->variableValues['input']);
            case 'removeLearningNeed':
                return $this->deleteLearningNeed($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createLearningNeed(LearningNeed $resource): LearningNeed
    {
        $result['result'] = [];

        // If studentId is set generate the url for it
        $studentUrl = null;
        if ($resource->getStudentId()) {
            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $resource->getStudentId()]);
        }

        // get all DTO info...
        $learningNeed = $this->dtoToLearningNeed($resource);

        // Do some checks and error handling
        $result = array_merge($result, $this->checkLearningNeedValues($learningNeed, $studentUrl));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save LearningNeed and connect student/participant to it
            $result = array_merge($result, $this->saveLearningNeed($result['learningNeed'], $studentUrl));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->handleResult($result['learningNeed']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));
        }

        // If any error was catched throw it
        if (isset($result['errorMessage'])) {
            throw new HttpException($result['errorMessage'], 400);
        }

        return $resourceResult;
    }

    public function updateLearningNeed(array $input): LearningNeed
    {
        $result['result'] = [];

        // If learningNeedUrl or learningNeedId is set generate the url and id for it, needed for eav calls later
        $learningNeedId = null;
        if ($input['learningNeedUrl']) {
            $learningNeedId = $this->commonGroundService->getUuidFromUrl($input['learningNeedUrl']);
        } elseif ($input['learningNeedId']) {
            $learningNeedId = explode('/',$input['learningNeedId']);
        }

        // Do some checks and error handling
        $result = array_merge($result, $this->checkLearningNeedValues($input, null, $learningNeedId));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save LearningNeed and connect student/participant to it
            $result = array_merge($result, $this->saveLearningNeed($result['learningNeed'], null, $learningNeedId));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->handleResult($result['learningNeed']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));
        }

        // If any error was catched throw it
        if (isset($result['errorMessage'])) {
            throw new HttpException($result['errorMessage'], 400);
        }

        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function deleteLearningNeed(array $learningNeed): ?LearningNeed
    {

        return null;
    }

    public function saveLearningNeed($learningNeed, $studentUrl = null, $learningNeedId = null) {
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

    public function addStudentToLearningNeed($studentUrl, $learningNeed) {
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

    private function checkLearningNeedValues($learningNeed, $studentUrl, $learningNeedId = null) {
        $result = [];
        if ($learningNeed['topicOther'] == 'OTHER' && !isset($learningNeed['applicationOther'])) {
            $result['errorMessage'] = 'Invalid request, desiredOutComesTopicOther is not set!';
        } elseif($learningNeed['application'] == 'OTHER' && !isset($learningNeed['applicationOther'])) {
            $result['errorMessage'] = 'Invalid request, desiredOutComesApplicationOther is not set!';
        } elseif ($learningNeed['level'] == 'OTHER' && !isset($learningNeed['levelOther'])) {
            $result['errorMessage'] = 'Invalid request, desiredOutComesLevelOther is not set!';
        } elseif ($learningNeed['offerDifference'] == 'YES_OTHER' && !isset($learningNeed['offerDifferenceOther'])) {
            $result['errorMessage'] = 'Invalid request, offerDifferenceOther is not set!';
        } elseif (isset($studentUrl) and !$this->commonGroundService->isResource($studentUrl)) {
            $result['errorMessage'] = 'Invalid request, studentId is not an existing edu/participant!';
        } elseif (isset($learningNeedId) and !$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            $result['errorMessage'] = 'Invalid request, learningNeedId and/or learningNeedUrl is not an existing eav/learning_need!';
        }
        // Make sure not to keep these values in the input/learningNeed body when doing and update
        unset($learningNeed['learningNeedId']); unset($learningNeed['learningNeedUrl']);
        unset($learningNeed['studentId']); unset($learningNeed['participations']);
        $result['learningNeed'] = $learningNeed;
        return $result;
    }

    private function dtoToLearningNeed(LearningNeed $resource) {
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
        // TODO: when participation subscriber is done, also make sure to connect and return the participations of this learningNeed
        // TODO: add 'verwijzingen' in EAV to connect learningNeeds to participations¿
        // Put together the expected result for Lifely:
        $resource = new LearningNeed();
        $resource->setLearningNeedDescription($learningNeed['description']);
        $resource->setLearningNeedMotivation($learningNeed['motivation']);
        $resource->setDesiredOutComesGoal($learningNeed['goal']);
        $resource->setDesiredOutComesTopic($learningNeed['topic']);
        $resource->setDesiredOutComesTopicOther($learningNeed['topicOther']);
        $resource->setDesiredOutComesApplication($learningNeed['application']);
        $resource->setDesiredOutComesApplicationOther($learningNeed['applicationOther']);
        $resource->setDesiredOutComesLevel($learningNeed['level']);
        $resource->setDesiredOutComesLevelOther($learningNeed['levelOther']);
        $resource->setOfferDesiredOffer($learningNeed['desiredOffer']);
        $resource->setOfferAdvisedOffer($learningNeed['advisedOffer']);
        $resource->setOfferDifference($learningNeed['offerDifference']);
        $resource->setOfferDifferenceOther($learningNeed['offerDifferenceOther']);
        $resource->setOfferEngagements($learningNeed['offerEngagements']);
        $resource->setParticipations(null);
        $this->entityManager->persist($resource);
        return $resource;
    }
}
