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
                return $this->removeLearningNeed($context['info']->variableValues['input']);
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

        // Transform DTO info to learningNeed body...
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

        // Transform input info to learningNeed body...
        $learningNeed = $this->inputToLearningNeed($input);

        // Do some checks and error handling
        $result = array_merge($result, $this->checkLearningNeedValues($learningNeed, null, $learningNeedId));

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

    public function removeLearningNeed(array $learningNeed): ?LearningNeed
    {
        $result['result'] = [];

        // If learningNeedUrl or learningNeedId is set generate the id for it, needed for eav calls later
        $learningNeedId = null;
        if ($learningNeed['learningNeedUrl']) {
            $learningNeedId = $this->commonGroundService->getUuidFromUrl($learningNeed['learningNeedUrl']);
        } elseif ($learningNeed['learningNeedId']) {
            $learningNeedId = explode('/',$learningNeed['learningNeedId']);
            if (is_array($learningNeedId)) {
                $learningNeedId = end($learningNeedId);
            }
        } else {
            throw new HttpException('Invalid request, please give a learningNeedId or learningNeedUrl when doing an update!', 400);
        }

        $result = array_merge($result, $this->deleteLearningNeed($learningNeedId));

        $result['result'] = False;
        if (isset($result['learningNeed'])){
            // Now put together the expected result in $result['result'] for Lifely:
            $result['result'] = True;
        }

        // If any error was catched throw it
        if (isset($result['errorMessage'])) {
            throw new HttpException($result['errorMessage'], 400);
        }
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

    public function deleteLearningNeed($id) {
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
            $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/learning_need!';
        }
        return $result;
    }

    public function removeLearningNeedFromStudent($learningNeedUrl, $studentUrl) {
        $result = [];
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            $participant['learningNeeds'] = array_filter($getParticipant['learningNeeds'], function($participantLearningNeed) use($learningNeedUrl) {
                return $participantLearningNeed != $learningNeedUrl;
            });
            $result['participant'] = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);
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
        // Get all info from the dto for creating a LearningNeed and return the body for this
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

    private function inputToLearningNeed(array $input) {
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
        $resource->setParticipations([]);
        $this->entityManager->persist($resource);
        return $resource;
    }
}
