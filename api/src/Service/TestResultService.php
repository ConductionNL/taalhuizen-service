<?php

namespace App\Service;

use App\Entity\TestResult;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class TestResultService
{
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private EDUService $eduService;

    public function __construct(
        CommonGroundService $commonGroundService,
        EntityManagerInterface $entityManager
    ) {
        $this->commonGroundService = $commonGroundService;
        $this->eavService = new EAVService($commonGroundService);
        $this->eduService = new EDUService($commonGroundService, $entityManager);
    }

    /**
     * This function updates or creates a test result with the given data.
     *
     * @param array       $testResult      Array that holds the test results data
     * @param array       $memo            Array that holds the memos data
     * @param string      $participationId ID of the participation
     * @param string|null $testResultUrl   Url of the test result as string
     *
     * @throws \Exception
     *
     * @return array Returns the test result and memo in a array
     */
    public function saveTestResult(array $testResult, array $memo, string $participationId, $testResultUrl = null): array
    {
        if (isset($participationId)) {
            // Create
            // Connect the participation and result in eav and save both objects
            $result = $this->addParticipationToTestResult($participationId, $testResult);
            $testResult = $result['testResult'];

            // Get the cc/person @id of the edu/participant of the eav/learningNeed of the eav/participation :)
            if ($this->commonGroundService->isResource($result['learningNeed']['participants'][0])) {
                $eduParticipant = $this->commonGroundService->getResource($result['learningNeed']['participants'][0]);
                $memo['author'] = $eduParticipant['person'];
                // maybe also check if this cc/person actually exist^ ?
            }
        } elseif (isset($testResultUrl)) {
            // Update
            // Save the testResult in EAV
            $testResult = $this->eduService->saveEavResult($testResult, $testResultUrl);
        } else {
            throw new Exception('[TestResultService]->saveTestResult, please give a participationId or a testResultUrl!');
        }
        // Save the memo
        $memo['topic'] = $testResult['@id'];
        $memo = $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'type' => 'memos']);

        return [
            'testResult' => $testResult,
            'memo'       => $memo,
        ];
    }

    /**
     * This function adds a participation to a test result.
     *
     * @param string $participationId ID of the participation
     * @param array  $testResult      Array with data of the test result
     *
     * @throws \Exception
     *
     * @return array Returns test result, participation an learning need in a array
     */
    private function addParticipationToTestResult($participationId, $testResult)
    {
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
        $testResult = $this->eduService->saveEavResult($testResult);

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
            'testResult'    => $testResult,
            'participation' => $participation,
            'learningNeed'  => $learningNeed,
        ];
    }

    /**
     * This function deletes a test result.
     *
     * @param string $id ID of the test result that will be deleted
     *
     * @throws \Exception
     */
    public function deleteTestResult(string $id)
    {
        $testResultUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'results', 'id' => $id]);
        // Check if this testResult exists
        if (!$this->commonGroundService->isResource($testResultUrl)) {
            throw new Exception('Invalid request, testResultId is not an existing edu/result!');
        }

        // Delete the memo(s) of this testResult (should always be one, but just in case, foreach)
        $memos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['topic'=>$testResultUrl])['hydra:member'];
        foreach ($memos as $memo) {
            $this->commonGroundService->deleteResource($memo);
        }

        if ($this->eavService->hasEavObject($testResultUrl)) {
            // Remove this result from the eav/participation
            $this->removeTestResultFromParticipation($testResultUrl);
        }
        $this->eavService->deleteResource(null, ['component'=>'edu', 'type'=>'results', 'id'=>$id]);
    }

    /**
     * This function removes a test result from a participation.
     *
     * @param string $testResultUrl Url of the test result as string
     *
     * @throws \Exception
     */
    private function removeTestResultFromParticipation(string $testResultUrl)
    {
        $testResult = $this->eavService->getObject('results', $testResultUrl, 'edu');
        if ($this->eavService->hasEavObject($testResult['participation'])) {
            $getParticipation = $this->eavService->getObject('participations', $testResult['participation']);
            if (isset($getParticipation['results'])) {
                $participation['results'] = array_values(array_filter($getParticipation['results'], function ($participationResult) use ($testResultUrl) {
                    return $participationResult != $testResultUrl;
                }));
                $this->eavService->saveObject($participation, 'participations', 'eav', $testResult['participation']);
            }
        }
        // only works when testResult is deleted after, because relation is not removed from the EAV testResult object in here
    }

    /**
     * This function fetches a test result from the given ID.
     *
     * @param string $id  ID of the test result that will be fetched
     * @param null   $url Url of the test result as string
     *
     * @throws \Exception
     *
     * @return array Returns a test result and memo in a array
     */
    public function getTestResult(string $id, $url = null): array
    {
        if (isset($id)) {
            $url = $this->commonGroundService->cleanUrl(['component'=>'edu', 'type'=>'results', 'id'=>$id]);
        } elseif (!isset($url)) {
            throw new Exception('[TestResultService]->getTestResult, expects an id or an url!');
        }

        // Get the edu/result from EAV and its memo from memo component
        if ($this->eavService->hasEavObject($url)) {
            $testResult = $this->eavService->getObject('results', $url, 'edu');

            $memos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['topic'=>$url])['hydra:member'];
            $memo = [];
            if (count($memos) > 0) {
                $memo = $memos[0];
            }
        } else {
            throw new Exception('Invalid request, '.$url.' is not an existing eav/edu/result!');
        }

        return [
            'testResult' => $testResult,
            'memo'       => $memo,
        ];
    }

    /**
     * This function fetches the test results from the given participation ID.
     *
     * @param string $participationId ID of the participation
     *
     * @throws \Exception
     *
     * @return array Returns test results in a array
     */
    public function getTestResults(string $participationId): array
    {
        if ($this->eavService->hasEavObject(null, 'participations', $participationId)) {
            // Get eav/participation
            $participation = $this->eavService->getObject('participations', null, 'eav', $participationId);
            // Get the edu/testResult urls for this participation and do gets on them
            $testResults = [];
            foreach ($participation['results'] as $result) {
                array_push($testResults, $this->getTestResult(null, $result));
            }
        } else {
            throw new Exception('Invalid request, '.$participationId.' is not an existing eav/participation !');
        }

        return $testResults;
    }

    /**
     * This function check the given test results values.
     *
     * @param array       $testResult      Array with data of the test result
     * @param string      $participationId ID of the participation
     * @param string|null $testResultUrl   Url of the test result as string
     *
     * @throws \Exception
     *
     * @return mixed Returns the test result as array
     */
    public function checkTestResultValues(array $testResult, string $participationId, $testResultUrl = null)
    {
        if (isset($testResultUrl) && !$this->commonGroundService->isResource($testResultUrl)) {
            throw new Exception('Invalid request, testResultId is not an existing edu/result!');
        }
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

    /**
     * This function converts the test result array to an TestResult object.
     *
     * @param array       $testResult      Array with data of the given test result
     * @param array       $memo            Array with data of the given memo
     * @param string|null $participationId ID of the participation as string
     *
     * @return \App\Entity\TestResult
     */
    public function handleResult(array $testResult, array $memo, $participationId = null): TestResult
    {
        // Put together the expected result for Lifely:
        $resource = new TestResult();
        // For some reason setting the id does not work correctly when done inside this function, so do it after calling this handleResult function instead!
//        $resource->setId(Uuid::getFactory()->fromString($testResult['id']));
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
