<?php

namespace App\Service;

use App\Entity\TestResult;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class TestResultService
{
    private EntityManagerInterface $entityManager;
    private $commonGroundService;
    private EAVService $eavService;
    private EDUService $eduService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, EAVService $eavService, EDUService $eduService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
        $this->eduService = $eduService;
    }

    public function saveTestResult(array $testResult, array $memo, $participationId, $testResultUrl = null) {
        //todo:make sure this works for updating as well

        // Connect the participation and result in eav and save both objects
        $result = $this->addParticipationToTestResult($participationId, $testResult, $testResultUrl);
        $testResult = $result['testResult'];

        // Get the cc/person @id of the edu/participant of the eav/learningNeed of the eav/participation :)
        if ($this->commonGroundService->isResource($result['learningNeed']['participants'][0])) {
            $eduParticipant = $this->commonGroundService->getResource($result['learningNeed']['participants'][0]);
            $memo['author'] = $eduParticipant['person'];
            // maybe also check if this cc/person actually exist^ ?
        }
        // Save the memo
        $memo['topic'] = $testResult['@id'];
        $memo = $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'type' => 'memos']);

        return [
            'testResult' => $testResult,
            'memo' => $memo
        ];
    }

    public function addParticipationToTestResult($participationId, $testResult, $testResultUrl = null) {
        // Check if participation already has testResults
        if ($this->eavService->hasEavObject(null, 'participations', $participationId)) {
            $participation = $this->eavService->getObject('participations', null, 'eav', $participationId);
        } else {
            throw new Exception('Invalid request, participationId is not an existing eav/participation!');
        }
        if ($this->eavService->hasEavObject($participation['learningNeed'], 'learning_needs')) {
            $learningNeed = $this->eavService->getObject('learning_needs', $participation['learningNeed']);
        } else {
            throw new Exception('Warning, participation is not connected to a learningNeed!');
        }
        if (count($learningNeed['participants']) == 0) {
            throw new Exception('Warning, the (eav/)learningNeed connected to this (eav/)participation has no student (edu/participant)!');
        }

        // Save the testResult in EAV with the EAV/participation connected to it
        $testResult['participation'] = $testResult['resource'] = $participation['@eav'];
        $testResult['participant'] = '/participants/'.$this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]);
        $testResult = $this->eduService->saveEavResult($testResult, $testResultUrl);

        if (isset($participation['results'])) {
            $updateParticipation['results'] = $participation['results'];
        } else {
            $updateParticipation['results'] = [];
        }
        // Update the eav/participation to add the EAV/edu/result to it
        if (!in_array($testResult['@id'], $updateParticipation['results'])) {
            array_push($updateParticipation['results'], $testResult['@id']);
            $participation = $this->eavService->saveObject($updateParticipation, 'participations', 'eav', null, $participationId);
        }
        return [
            'testResult' => $testResult,
            'participation' => $participation,
            'learningNeed' => $learningNeed
        ];
    }

    //todo:
    public function deleteTestResult($id) {
//        if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
//            $result['participants'] = [];
//            // Get the learningNeed from EAV
//            $learningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $id);
//
//            // Remove this learningNeed from all EAV/edu/participants
//            foreach ($learningNeed['participants'] as $studentUrl) {
//                $studentResult = $this->removeLearningNeedFromStudent($learningNeed['@eav'], $studentUrl);
//                if (isset($studentResult['participant'])) {
//                    // Add $studentUrl to the $result['participants'] because this is convenient when testing or debugging (mostly for us)
//                    array_push($result['participants'], $studentResult['participant']['@id']);
//                }
//            }
//
//            foreach ($learningNeed['participations'] as $participationUrl) {
//                $this->participationService->deleteParticipation(null, $participationUrl, True);
//            }
//
//            // Delete the learningNeed in EAV
//            $this->eavService->deleteObject($learningNeed['eavId']);
//            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
//            $result['learningNeed'] = $learningNeed;
//        } else {
//            $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/learning_need!';
//        }
//        return $result;
    }

    //todo:
//    public function removeTestResultFromParticipation($learningNeedUrl, $studentUrl) {
//        $result = [];
//        if ($this->eavService->hasEavObject($studentUrl)) {
//            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
//            if (isset($getParticipant['learningNeeds'])) {
//                $participant['learningNeeds'] = array_values(array_filter($getParticipant['learningNeeds'], function($participantLearningNeed) use($learningNeedUrl) {
//                    return $participantLearningNeed != $learningNeedUrl;
//                }));
//                $result['participant'] = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);
//            }
//        }
//        // only works when learningNeed is deleted after, because relation is not removed from the EAV learningNeed object in here
//        return $result;
//    }

    //todo:
    public function getTestResult($id, $url = null) {
//        $result = [];
//        // Get the learningNeed from EAV and add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
//        if (isset($id)) {
//            if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
//                $learningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $id);
//                $result['learningNeed'] = $learningNeed;
//            } else {
//                $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/learning_need!';
//            }
//        } elseif(isset($url)) {
//            if ($this->eavService->hasEavObject($url)) {
//                $learningNeed = $this->eavService->getObject('learning_needs', $url);
//                $result['learningNeed'] = $learningNeed;
//            } else {
//                $result['errorMessage'] = 'Invalid request, '. $url .' is not an existing eav/learning_need!';
//            }
//        }
//        return $result;
    }

    //todo:
    public function getTestResults($studentId) {
//        // Get the eav/edu/participant learningNeeds from EAV and add the $learningNeeds @id's to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
//        if ($this->eavService->hasEavObject(null, 'participants', $studentId, 'edu')) {
//            $result['learningNeeds'] = [];
//            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $studentId]);
//            $participant = $this->eavService->getObject('participants', $studentUrl, 'edu');
//            foreach ($participant['learningNeeds'] as $learningNeedUrl) {
//                $learningNeed = $this->getLearningNeed(null, $learningNeedUrl);
//                if (isset($learningNeed['learningNeed'])) {
//                    array_push($result['learningNeeds'], $learningNeed['learningNeed']);
//                } else {
//                    array_push($result['learningNeeds'], ['errorMessage' => $learningNeed['errorMessage']]);
//                }
//            }
//        } else {
//            // Do not throw an error, because we want to return an empty array in this case
//            $result['message'] = 'Warning, '. $studentId .' is not an existing eav/edu/participant!';
//        }
//        return $result;
    }

    public function checkTestResultValues($testResult, $participationId, $testResultId = null) {
        //todo: stuff for an update instead of create
//        if (isset($testResultId)) {
//            if (!$this->commonGroundService->isResource()) {
//
//            }
//        }
        if (isset($participationId) and !$this->eavService->hasEavObject(null, 'participations', $participationId)) {
            throw new Exception('Invalid request, participationId is not an existing eav/participation!');
        }
        if ($testResult['topicOther'] == 'OTHER' && !isset($testResult['topicOther'])) {
            throw new Exception('Invalid request, outComesTopicOther is not set!');
        }
        if ($testResult['application'] == 'OTHER' && !isset($testResult['applicationOther'])) {
            throw new Exception('Invalid request, outComesApplicationOther is not set!');
        }
        if ($testResult['level'] == 'OTHER' && !isset($testResult['levelOther'])) {
            throw new Exception('Invalid request, outComesLevelOther is not set!');
        }
        // Make sure not to keep these values in the input/testResult body when doing and update
        unset($testResult['testResultId']);
        return $testResult;
    }

    public function handleResult($testResult, $memo, $participationId = null) {
        // Put together the expected result for Lifely:
        $resource = new TestResult();
        // For some reason setting the id does not work correctly when done inside this function, so do it after calling this handleResult function instead!
//        $resource->setId(Uuid::getFactory()->fromString($testResult['id']));
        // todo: if isset checks where needed:
        $resource->setOutComesGoal($testResult['goal']);
        $resource->setOutComesTopic($testResult['topic']);
        $resource->setOutComesTopicOther($testResult['topicOther']);
        $resource->setOutComesApplication($testResult['application']);
        $resource->setOutComesApplicationOther($testResult['applicationOther']);
        $resource->setOutComesLevel($testResult['level']);
        $resource->setOutComesLevelOther($testResult['levelOther']);
        $resource->setExamUsedExam($testResult['name']);
        $resource->setExamDate($testResult['completionDate']);
        $resource->setExamMemo($memo['description']);

        if (isset($participationId)) {
            $resource->setParticipationId($participationId);
        }
        $this->entityManager->persist($resource);
        return $resource;
    }
}