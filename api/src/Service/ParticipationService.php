<?php

namespace App\Service;

use App\Entity\LearningNeed;
use App\Entity\Participation;
use App\Service\EAVService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class ParticipationService
{
    private EntityManagerInterface $entityManager;
    private $commonGroundService;
    private EAVService $eavService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, EAVService $eavService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
    }

    public function saveParticipation($participation, $learningNeedId = null, $participationId = null) {
        // Save the participation in EAV
        if (isset($participationId)) {
            // Update
            $participation = $this->eavService->saveObject($participation, 'participations', 'eav', null, $participationId);
        } else {
            // Create
            $participation = $this->eavService->saveObject($participation, 'participations');
        }

        // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        $result['participation'] = $participation;

        // Connect EAV/learningNeed to the participation
        $result = array_merge($result, $this->connectLearningNeedToParticipation($learningNeedId, $participation));

        return $result;
    }

    public function connectLearningNeedToParticipation($learningNeedId, $participation) {
        $result = [];
        // should already be checked but just in case:
        if(!$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            throw new Exception('Invalid request, learningNeedId is not an existing eav/learning_need!');
        }
        $getLearningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $learningNeedId);
        if (isset($getLearningNeed['participations'])) {
            $learningNeed['participations'] = $getLearningNeed['participations'];
        } else {
            $learningNeed['participations'] = [];
        }

        // Connect the learningNeed in EAV to the EAV/participation connected to it
        if (!in_array($participation['@id'], $learningNeed['participations'])) {
            array_push($learningNeed['participations'], $participation['@id']);
            $learningNeed = $this->eavService->saveObject($learningNeed, 'learning_needs', 'eav', null, $learningNeedId);

            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['learningNeed'] = $learningNeed;

            // Update the participation to add the EAV/learningNeed to it
            $updateParticipation['learningNeed'] = $learningNeed['@id'];
            $participation = $this->eavService->saveObject($updateParticipation, 'participations', 'eav', $participation['@eav']);

            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $result;
    }

    public function deleteParticipation($id) {
        //todo:
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
//            // Delete the learningNeed in EAV
//            $this->eavService->deleteObject($learningNeed['eavId']);
//            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
//            $result['learningNeed'] = $learningNeed;
//        } else {
//            $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/learning_need!';
//        }
//        return $result;
    }

//    public function removeLearningNeedFromStudent($learningNeedUrl, $studentUrl) {
//        $result = [];
//        if ($this->eavService->hasEavObject($studentUrl)) {
//            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
//            $participant['learningNeeds'] = array_filter($getParticipant['learningNeeds'], function($participantLearningNeed) use($learningNeedUrl) {
//                return $participantLearningNeed != $learningNeedUrl;
//            });
//            $result['participant'] = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);
//        }
//        return $result;
//    }

    public function getParticipation($id, $url = null) {
        $result = [];
        // Get the participation from EAV and add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'participations', $id)) {
                $participation = $this->eavService->getObject('participations', null, 'eav', $id);
                $result['participation'] = $participation;
            } else {
                $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/participation!';
            }
        } elseif(isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $participation = $this->eavService->getObject('participations', $url);
                $result['participation'] = $participation;
            } else {
                $result['errorMessage'] = 'Invalid request, '. $url .' is not an existing eav/learning_need!';
            }
        }
        return $result;
    }

    public function getParticipations($studentId) {
        //todo:
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
//            $result['message'] = 'Warning, '. $studentId .' is not an existing eav/edu/participant!';
//        }
//        return $result;
    }

    public function checkParticipationValues($participation, $aanbiederUrl, $learningNeedId, $participationId = null) {
        $result = [];
        if (!isset($learningNeedId)) {
            $result['errorMessage'] = 'Invalid request, learningNeedId is not set!';
        } elseif(!$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            $result['errorMessage'] = 'Invalid request, learningNeedId is not an existing eav/learning_need!';
        } elseif (isset($participation['aanbiederId']) && isset($participation['aanbiederName'])) {
            $result['errorMessage'] = 'Invalid request, aanbiederId and aanbiederName are both set! Please only give one of the two.';
        } elseif ($participation['topicOther'] == 'OTHER' && !isset($participation['applicationOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesTopicOther is not set!';
        } elseif($participation['application'] == 'OTHER' && !isset($participation['applicationOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesApplicationOther is not set!';
        } elseif ($participation['level'] == 'OTHER' && !isset($participation['levelOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesLevelOther is not set!';
        } elseif(isset($participation['startDate']) && isset($participation['endDate']) && $participation['startDate'] >= $participation['endDate']) {
            $result['errorMessage'] = 'Invalid request, detailsEndDate needs to be later than detailsStartDate!';
        } elseif (isset($aanbiederUrl) and !$this->commonGroundService->isResource($aanbiederUrl)) {
            $result['errorMessage'] = 'Invalid request, aanbiederId is not an existing cc/organization!';
        } elseif (isset($participationId) and !$this->eavService->hasEavObject(null, 'participations', $participationId)) {
            $result['errorMessage'] = 'Invalid request, participationId is not an existing eav/participation!';
        }
        // Make sure not to keep these values in the input/participation body when doing and update
        unset($participation['participationId']);
        $result['participation'] = $participation;
        return $result;
    }

    public function handleResult($participation, $learningNeedId = null) {
        // Put together the expected result for Lifely:
        $resource = new Participation();
        // For some reason setting the id does not work correctly when done inside this function, so do it after calling this handleResult function instead!
//        $resource->setId(Uuid::getFactory()->fromString($participation['id']));
        if (isset($participation['status'])) {
            $resource->setStatus($participation['status']);
        }
        $resource->setAanbiederId($participation['aanbiederId']);
        $resource->setAanbiederName($participation['aanbiederName']);
        $resource->setAanbiederNote($participation['aanbiederNote']);
        $resource->setOfferName($participation['offerName']);
        $resource->setOfferCourse($participation['offerCourse']);
        $resource->setOutComesGoal($participation['goal']);
        $resource->setOutComesTopic($participation['topic']);
        $resource->setOutComesTopicOther($participation['topicOther']);
        $resource->setOutComesApplication($participation['application']);
        $resource->setOutComesApplicationOther($participation['applicationOther']);
        $resource->setOutComesLevel($participation['level']);
        $resource->setOutComesLevelOther($participation['levelOther']);
        $resource->setDetailsIsFormal($participation['isFormal']);
        $resource->setDetailsGroupFormation($participation['groupFormation']);
        $resource->setDetailsTotalClassHours($participation['totalClassHours']);
        $resource->setDetailsCertificateWillBeAwarded($participation['certificateWillBeAwarded']);
        if (isset($participation['startDate'])) {
            $resource->setDetailsStartDate(new \DateTime($participation['startDate']));
        }
        if (isset($participation['endDate'])) {
            $resource->setDetailsEndDate(new \DateTime($participation['endDate']));
        }
        $resource->setDetailsEngagements($participation['engagements']);
        if (isset($participation['presenceStartDate'])) {
            $resource->setPresenceStartDate(new \DateTime($participation['presenceStartDate']));
        }
        if (isset($participation['presenceEndDate'])) {
            $resource->setPresenceEndDate(new \DateTime($participation['presenceEndDate']));
        }
        if (isset($participation['presenceEndParticipationReason'])) {
            $resource->setPresenceEndParticipationReason($participation['presenceEndParticipationReason']);
        }

        if (isset($learningNeedId)) {
            $resource->setLearningNeedId($learningNeedId);
        }
        $this->entityManager->persist($resource);
        return $resource;
    }

    public function handleResultJson($participation, $learningNeedId = null) {
        $resource['id'] = '/participations/'.$participation['id'];
        if (isset($participation['status'])) {
            $resource['status'] = $participation['status'];
        }
        $resource['aanbiederId'] = $participation['aanbiederId'];
        $resource['aanbiederName'] = $participation['aanbiederName'];
        $resource['aanbiederNote'] = $participation['aanbiederNote'];
        $resource['offerName'] = $participation['offerName'];
        $resource['offerCourse'] = $participation['offerCourse'];
        $resource['outComesGoal'] = $participation['goal'];
        $resource['outComesTopic'] = $participation['topic'];
        $resource['outComesTopicOther'] = $participation['topicOther'];
        $resource['outComesApplication'] = $participation['application'];
        $resource['outComesApplicationOther'] = $participation['applicationOther'];
        $resource['outComesLevel'] = $participation['level'];
        $resource['outComesLevelOther'] = $participation['levelOther'];
        $resource['detailsIsFormal'] = $participation['isFormal'];
        $resource['detailsGroupFormation'] = $participation['groupFormation'];
        $resource['detailsTotalClassHours'] = $participation['totalClassHours'];
        $resource['detailsCertificateWillBeAwarded'] = $participation['certificateWillBeAwarded'];
        $resource['detailsStartDate'] = $participation['startDate'];
        $resource['detailsEndDate'] = $participation['endDate'];
        $resource['detailsEngagements'] = $participation['engagements'];
        $resource['presenceStartDate'] = $participation['presenceStartDate'];
        $resource['presenceEndDate'] = $participation['presenceEndDate'];
        $resource['presenceEndParticipationReason'] = $participation['presenceEndParticipationReason'];
        if (isset($learningNeedId)) {
            $resource['learningNeedId'] = '/learning_needs/'.$learningNeedId;
        }
        return $resource;
    }
}
