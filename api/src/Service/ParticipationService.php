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
        if (isset($learningNeedId)) {
            $result = array_merge($result, $this->addParticipationToLearningNeed($participation, $learningNeedId));
        }

        return $result;
    }

    private function addParticipationToLearningNeed($participation, $learningNeedId) {
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

        // Connect the learningNeed in EAV to the EAV/participation
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
        if ($this->eavService->hasEavObject(null, 'participations', $id)) {
            // Get the participation from EAV
            $participation = $this->eavService->getObject('participations', null, 'eav', $id);

            // Remove this participation from the EAV/edu/learningNeed
            $result = $this->removeParticipationFromLearningNeed($participation['@eav'], $participation['learningNeed']);

            // Delete the participation in EAV
            $this->eavService->deleteObject($participation['eavId']);
            // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        } else {
            $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/participation!';
        }
        return $result;
    }

    private function removeParticipationFromLearningNeed($participationUrl, $learningNeedUrl) {
        $result = [];
        if ($this->eavService->hasEavObject($learningNeedUrl)) {
            $getLearningNeed = $this->eavService->getObject('learning_needs', $learningNeedUrl);
            $learningNeed['participations'] = array_filter($getLearningNeed['participations'], function($learningNeedParticipation) use($participationUrl) {
                return $learningNeedParticipation != $participationUrl;
            });
            $result['learningNeed'] = $this->eavService->saveObject($learningNeed, 'learning_needs', 'eav', $learningNeedUrl);
        }
        return $result;
    }

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
                $result['errorMessage'] = 'Invalid request, '. $url .' is not an existing eav/participation!';
            }
        }
        return $result;
    }

    public function getParticipations($learningNeedId) {
        // Get the eav/LearningNeed participations from EAV and add the $participations @id's to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        if ($this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            $result['participations'] = [];
            $learningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $learningNeedId);
            foreach ($learningNeed['participations'] as $participationUrl) {
                $participation = $this->getParticipation(null, $participationUrl);
                if (isset($participation['participation'])) {
                    array_push($result['participations'], $participation['participation']);
                } else {
                    array_push($result['participations'], ['errorMessage' => $participation['errorMessage']]);
                }
            }
        } else {
            $result['message'] = 'Warning, '. $learningNeedId .' is not an existing eav/learning_need!';
        }
        return $result;
    }

    public function checkParticipationValues($participation, $aanbiederUrl, $learningNeedId, $participationId = null) {
        $result = [];
        if (isset($participation['aanbiederId']) && isset($participation['aanbiederName'])) {
            $result['errorMessage'] = 'Invalid request, aanbiederId and aanbiederName are both set! Please only give one of the two.';
        } elseif ($participation['topicOther'] == 'OTHER' && !isset($participation['applicationOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesTopicOther is not set!';
        } elseif($participation['application'] == 'OTHER' && !isset($participation['applicationOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesApplicationOther is not set!';
        } elseif ($participation['level'] == 'OTHER' && !isset($participation['levelOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesLevelOther is not set!';
        } elseif (isset($aanbiederUrl) and !$this->commonGroundService->isResource($aanbiederUrl)) {
            $result['errorMessage'] = 'Invalid request, aanbiederId is not an existing cc/organization!';
        } elseif (isset($participationId) and !$this->eavService->hasEavObject(null, 'participations', $participationId)) {
            $result['errorMessage'] = 'Invalid request, participationId is not an existing eav/participation!';
        } elseif(isset($learningNeedId) && !$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            $result['errorMessage'] = 'Invalid request, learningNeedId is not an existing eav/learning_need!';
        }
        if(isset($participation['startDate']) && isset($participation['endDate'])) {
            if ($participation['startDate'] instanceof \DateTime && $participation['endDate'] instanceof \DateTime) {
                $startDate = $participation['startDate'];
                $endDate = $participation['endDate'];
            } else {
                $startDate = new \DateTime($participation['startDate']); $startDate->format('Y-m-d H:i:s');
                $endDate = new \DateTime($participation['endDate']); $endDate->format('Y-m-d H:i:s');
            }
            if ($startDate >= $endDate) {
                $result['errorMessage'] = 'Invalid request, detailsEndDate needs to be later than detailsStartDate!';
            }
        }
        if(isset($participation['presenceStartDate']) && isset($participation['presenceEndDate'])) {
            if ($participation['presenceStartDate'] instanceof \DateTime && $participation['presenceEndDate'] instanceof \DateTime) {
                $startDate = $participation['presenceStartDate'];
                $endDate = $participation['presenceEndDate'];
            } else {
                $startDate = new \DateTime($participation['presenceStartDate']); $startDate->format('Y-m-d H:i:s');
                $endDate = new \DateTime($participation['presenceEndDate']); $endDate->format('Y-m-d H:i:s');
            }
            if ($startDate >= $endDate) {
                $result['errorMessage'] = 'Invalid request, presenceEndDate needs to be later than presenceStartDate!';
            }
        }
        // Make sure not to keep these values in the input/participation body when doing and update
        unset($participation['participationId']); unset($participation['learningNeedId']);
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
        $resource->setPresenceEndParticipationReason($participation['presenceEndParticipationReason']);

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
