<?php

namespace App\Service;

use App\Entity\LearningNeed;
use App\Entity\Participation;
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

    public function handleGettingParticipation($participationId)
    {
        if (isset($participationId)) {
            // This should be checked with checkParticipationValues, but just in case:
            if (!$this->eavService->hasEavObject(null, 'participations', $participationId)) {
                return $result['errorMessage'] = $result['errorMessage'] = 'Invalid request, '.$participationId.' is not an existing eav/participation!';
            }
            // Update
            $participation = $this->eavService->saveObject($participation, 'participations', 'eav', null, $participationId);
        } else {
            // Create
            if (isset($participation['aanbiederName'])) {
                $participation['status'] = 'ACTIVE';
            } else {
                $participation['status'] = 'REFERRED';
            }
            $participation = $this->eavService->saveObject($participation, 'participations');
        }

        return $participation;
    }

    public function saveParticipation($participation, $learningNeedId = null, $participationId = null)
    {
        // Save the participation in EAV
        $participation = $this->handleGettingParticipation($participationId);

        // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        $result['participation'] = $participation;

        // Connect EAV/learningNeed to the participation
        if (isset($learningNeedId)) {
            $result = array_merge($result, $this->addLearningNeedToParticipation($learningNeedId, $participation));
        }

        // Connect provider/aanbieder cc/organization to this participation, in order to later get all participations of a provider
        if (isset($participation['aanbiederId'])) {
            $result = array_merge($result, $this->addAanbiederToParticipation($participation['aanbiederId'], $participation));
        }

        $result = array_merge($result, $this->updateParticipationStatus($result['participation']));

        // Now put together the expected result in $result['result'] for Lifely:
        return $this->handleResult($result['participation'], $learningNeedId);
    }

    private function addLearningNeedToParticipation($learningNeedId, $participation)
    {
        $result = [];
        // should already be checked but just in case:
        if (!$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            throw new Exception('Invalid request, learningNeedId is not an existing eav/learning_need!');
        }
        $getLearningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $learningNeedId);
        if (isset($getLearningNeed['participations'])) {
            $learningNeed['participations'] = $getLearningNeed['participations'];
        }
        if (!isset($learningNeed['participations'])) {
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

    private function addAanbiederToParticipation($aanbiederId, $participation)
    {
        $result = [];
        $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $aanbiederId]);
        // should already be checked but just in case:
        if (!$this->commonGroundService->isResource($aanbiederUrl)) {
            $result['errorMessage'] = 'Invalid request, aanbiederId is not an existing cc/organization!';
        }

        // Check if aanbieder already has an EAV object
        if ($this->eavService->hasEavObject($aanbiederUrl)) {
            $getOrganization = $this->eavService->getObject('organizations', $aanbiederUrl, 'cc');
            $organization['participations'] = $getOrganization['participations'];
        }
        if (!isset($organization['participations'])) {
            $organization['participations'] = [];
        }

        // Connect the organization in EAV to the EAV/participation
        if (!in_array($participation['@id'], $organization['participations'])) {
            array_push($organization['participations'], $participation['@id']);
            $organization = $this->eavService->saveObject($organization, 'organizations', 'cc', $aanbiederUrl);

            // Add $organization to the $result['organization'] because this is convenient when testing or debugging (mostly for us)
            $result['organization'] = $organization;

            // Update the participation to add the cc/organization to it
            $updateParticipation['aanbieder'] = $organization['@id'];
            $participation = $this->eavService->saveObject($updateParticipation, 'participations', 'eav', $participation['@eav']);

            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $result;
    }

    public function deleteParticipation($id, $url = null)
    {
        $participation = $this->getParticipation($id, $url);

        // Remove this participation from the EAV/edu/learningNeed
        $result = $this->removeLearningNeedFromParticipation($participation['learningNeed'], $participation['@eav']);

        // Remove this participation from the EAV/cc/organization
        $result = $this->removeAanbiederFromParticipation($participation['aanbieder'], $participation['@eav']);

        // Delete the participation in EAV
        $this->eavService->deleteObject($participation['eavId']);
        // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        $result['participation'] = $participation;

        return $result;
    }

    private function removeLearningNeedFromParticipation($learningNeedUrl, $participationUrl)
    {
        $result = [];
        if (!$participationUrl) {
            if ($this->eavService->hasEavObject($learningNeedUrl)) {
                $getLearningNeed = $this->eavService->getObject('learning_needs', $learningNeedUrl);
                if (isset($getLearningNeed['participations'])) {
                    $learningNeed['participations'] = array_values(array_filter($getLearningNeed['participations'], function ($learningNeedParticipation) use ($participationUrl) {
                        return $learningNeedParticipation != $participationUrl;
                    }));
                    $result['learningNeed'] = $this->eavService->saveObject($learningNeed, 'learning_needs', 'eav', $learningNeedUrl);
                }
            }
        }
        // only works when participation is deleted after, because relation is not removed from the EAV participation object in here
        return $result;
    }

    private function removeAanbiederFromParticipation($aanbiederUrl, $participationUrl)
    {
        $result = [];
        if (isset($aanbiederUrl)) {
            if ($this->eavService->hasEavObject($aanbiederUrl)) {
                $getOrganization = $this->eavService->getObject('organizations', $aanbiederUrl, 'cc');
                if (isset($getOrganization['participations'])) {
                    $organization['participations'] = array_values(array_filter($getOrganization['participations'], function ($organizationParticipation) use ($participationUrl) {
                        return $organizationParticipation != $participationUrl;
                    }));
                    $result['organization'] = $this->eavService->saveObject($organization, 'organizations', 'cc', $aanbiederUrl);
                }
            }
        }
        // only works when participation is deleted after, because relation is not removed from the EAV participation object in here
        return $result;
    }

    public function getParticipation($id, $url = null)
    {
        $result = [];
        // Get the participation from EAV and add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'participations', $id)) {
                $participation = $this->eavService->getObject('participations', null, 'eav', $id);
                $result['participation'] = $participation;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/participation!';
            }
        } elseif (isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $participation = $this->eavService->getObject('participations', $url);
                $result['participation'] = $participation;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$url.' is not an existing eav/participation!';
            }
        }
        if (isset($result['participation'])) {
            $result = array_merge($result, $this->updateParticipationStatus($result['participation']));
        }

        return $result;
    }

    public function getParticipations($learningNeedId)
    {
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
            // Do not throw an error, because we want to return an empty array in this case
            $result['message'] = 'Warning, '.$learningNeedId.' is not an existing eav/learning_need!';
        }

        return $result;
    }

    public function handleParticipationStatus($participation)
    {
        if ((isset($participation['mentor']) || isset($participation['group']))) {
            $updateParticipation['status'] = 'ACTIVE';
            if (isset($participation['presenceEndDate'])) {
                $now = new \DateTime('now');
                $now->format('Y-m-d H:i:s');
                $endDate = new \DateTime($participation['presenceEndDate']);
                $endDate->format('Y-m-d H:i:s');
                if ($now > $endDate) {
                    $updateParticipation['status'] = 'COMPLETED';
                }
            }
        } elseif (isset($participation['aanbiederName'])) {
            $updateParticipation['status'] = 'ACTIVE';
        } else {
            $updateParticipation['status'] = 'REFERRED';
        }

        return $updateParticipation;
    }

    public function updateParticipationStatus($participation)
    {
        $result = [];

        // Check what the current status should be
        $updateParticipation = $this->handleParticipationStatus($participation);
        // Check if the status needs to be changed
        if ($participation['status'] != $updateParticipation['status']) {
            // Update status
            $participation = $this->eavService->saveObject($updateParticipation, 'participations', 'eav', $participation['@eav']);

            // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $result;
    }

    public function getEmployeeParticipations($mentorUrl)
    {
        if ($this->eavService->hasEavObject($mentorUrl)) {
            $getEmployee = $this->eavService->getObject('employees', $mentorUrl, 'mrc');
            $employee['participations'] = $getEmployee['participations'];
        }
        if (!isset($employee['participations'])) {
            $employee['participations'] = [];
        }

        return $employee;
    }

    public function addMentorToParticipation($mentorUrl, $participation)
    {
        $result = [];
        // Make sure this participation has no mentor or group set
        if (isset($participation['mentor']) || isset($participation['group'])) {
            return ['errorMessage' => 'Warning, this participation already has a mentor or group set!'];
        }

        // Check if mentor already has an EAV object
        $employee = $this->getEmployeeParticipations($mentorUrl);

        // Save the employee in EAV with the EAV/participant connected to it
        if (!in_array($participation['@id'], $employee['participations'])) {
            array_push($employee['participations'], $participation['@id']);
            $employee = $this->eavService->saveObject($employee, 'employees', 'mrc', $mentorUrl);

            // Add $employee to the $result['employee'] because this is convenient when testing or debugging (mostly for us)
            $result['employee'] = $employee;

            // Update the participant to add the mrc/employee to it
            $updateParticipation['mentor'] = $employee['@id'];
            $updateParticipation['status'] = 'ACTIVE';
            $participation = $this->eavService->saveObject($updateParticipation, 'participations', 'eav', $participation['@eav']);

            // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;

            $learningNeed = $this->eavService->getObject('learning_needs', $participation['learningNeed']);
            $participant['mentor'] = $mentorUrl;
            $this->commonGroundService->updateResource($participant, $learningNeed['participants'][0]);
        }

        return $this->handleResult($result['participation']);
    }

    public function removeMentorFromParticipation($mentorUrl, $participation)
    {
        $result = [];
        if (!isset($participation['mentor'])) {
            return ['errorMessage' => 'Invalid request, this participation has no mentor!'];
        }
        if ($participation['mentor'] != $mentorUrl) {
            return ['errorMessage' => 'Invalid request, this participation has a different mentor!'];
        }
        if (!$this->eavService->hasEavObject($mentorUrl)) {
            return ['errorMessage' => 'Invalid request, '.$mentorUrl.' is not an existing eav/mrc/employee!'];
        }

        $learningNeed = $this->eavService->getObject('learning_needs', $participation['learningNeed']);
        $participant['mentor'] = '';
        $this->commonGroundService->updateResource($participant, $learningNeed['participants'][0]);

        // Update eav/mrc/employee to remove the participation from it
        $getEmployee = $this->eavService->getObject('employees', $mentorUrl, 'mrc');
        if (isset($getEmployee['participations'])) {
            $employee['participations'] = array_values(array_filter($getEmployee['participations'], function ($employeeParticipation) use ($participation) {
                return $employeeParticipation != $participation['@eav'];
            }));
            $result['employee'] = $this->eavService->saveObject($employee, 'employees', 'mrc', $mentorUrl);
        }
        // Update eav/participation to remove the EAV/mrc/employee from it
        $updateParticipation['mentor'] = null;

        return $this->updateParticipation($participation);
    }

    public function addGroupToParticipation($groupUrl, $participation)
    {
        $result = [];
        // Make sure this participation has no mentor or group set
        $this->checkMentor($participation);
        // Check if group already has an EAV object
        $group['participations'] = $this->checkEAVGroup($participation);

        // Save the group in EAV with the EAV/participant connected to it
        if (!in_array($participation['@id'], $group['participations'])) {
            $group['participations'][] = $participation['@id'];
            $learningNeed = $this->eavService->getObject('learning_needs', $participation['learningNeed']);
            $participantId = $this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]);
            $group['participants'][] = '/participants/'.$participantId;
            $group = $this->eavService->saveObject($group, 'groups', 'edu', $groupUrl);

            // Add $group to the $result['group'] because this is convenient when testing or debugging (mostly for us)
            $result['group'] = $group;

            // Update the participant to add the edu/group to it
            $updateParticipation['group'] = $group['@id'];
            $updateParticipation['status'] = 'ACTIVE';
            $participation = $this->eavService->saveObject($updateParticipation, 'participations', 'eav', $participation['@eav']);

            // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $this->handleResult($result['participation']);
    }

    public function checkMentor($participation)
    {
        if (isset($participation['mentor']) || isset($participation['group'])) {
            return ['errorMessage' => 'Warning, this participation already has a mentor or group set!'];
        }

        return false;
    }

    public function checkEAVGroup($groupUrl)
    {
        if ($this->eavService->hasEavObject($groupUrl)) {
            $getGroup = $this->eavService->getObject('groups', $groupUrl, 'edu');
            $group['participations'] = $getGroup['participations'];
            $group['participants'] = $getGroup['participants'];
        }
        if (!isset($group['participations'])) {
            $group['participations'] = [];
            $group['participants'] = $this->commonGroundService->getResource($groupUrl)['participants'];
        }
        if (isset($group['participants'])) {
            foreach ($group['participants'] as &$participant) {
                $participant = '/participants/'.$participant['id'];
            }
        }

        return $group['participations'];
    }

    public function removeGroupFromParticipation($groupUrl, $participation)
    {
        $result = [];
        $this->errorRemoveGroupFromParticipation($groupUrl, $participation);

        // Update eav/edu/group to remove the participation from it
        $getGroup = $this->eavService->getObject('groups', $groupUrl, 'edu');
        if (isset($getGroup['participations'])) {
            $group['participations'] = array_values(array_filter($getGroup['participations'], function ($groupParticipation) use ($participation) {
                return $groupParticipation != $participation['@eav'];
            }));
            $learningNeed = $this->eavService->getObject('learning_needs', $participation['learningNeed']);
            $participantId = $this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]);
            $group['participants'] = array_values(array_filter($getGroup['participants'], function ($groupParticipant) use ($participantId) {
                return $groupParticipant['id'] != $participantId;
            }));
            if (isset($group['participants'])) {
                foreach ($group['participants'] as &$participant) {
                    $participant = '/participants/'.$participant['id'];
                }
            }
            $result['group'] = $this->eavService->saveObject($group, 'groups', 'edu', $groupUrl);
        }
        // Update eav/participation to remove the EAV/edu/group from it
        $updateParticipation['group'] = null;

        return $this->updateParticipation($participation);
    }

    public function errorRemoveGroupFromParticipation($groupUrl, $participation)
    {
        $this->checkGroupInput($participation);
        $this->checkGroup($groupUrl, $participation);

        if (!$this->eavService->hasEavObject($groupUrl)) {
            return ['errorMessage' => 'Invalid request, '.$groupUrl.' is not an existing eav/edu/group!'];
        }

        return false;
    }

    public function checkGroupInput($participation)
    {
        if (!isset($participation['group'])) {
            return ['errorMessage' => 'Invalid request, this participation has no group!'];
        }

        return false;
    }

    public function checkGroup($groupUrl, $participation)
    {
        if ($participation['group'] != $groupUrl) {
            return ['errorMessage' => 'Invalid request, this participation has a different group!'];
        }

        return false;
    }

    public function checkParticipationValues($participation, $aanbiederUrl, $learningNeedId, $participationId = null)
    {
        $result = [];
        $this->checkParticipationRequiredFields($participation, $aanbiederUrl, $learningNeedId, $participationId);
        $this->checkParticipationValuesStartDate($participation);
        $this->checkParticipationValuesPresenceStartDate($participation);
        // Make sure not to keep these values in the input/participation body when doing and update
        unset($participation['participationId']);
        unset($participation['learningNeedId']);
        $result['participation'] = $participation;

        return $result;
    }

    public function checkParticipationRequiredFields($participation, $aanbiederUrl, $learningNeedId, $participationId = null)
    {
        $this->checkAanbieder($participation);
        $this->checkTopic($participation);
        $this->checkApplication($participation);
        $this->checkLevel($participation);
        $this->checkAanbiederUrl($aanbiederUrl);
        $this->checkParticipationId($participationId);
        $this->checkLearningNeedId($learningNeedId);
    }

    public function checkAanbieder($participation)
    {
        if (isset($participation['aanbiederId']) && isset($participation['aanbiederName'])) {
            $result['errorMessage'] = 'Invalid request, aanbiederId and aanbiederName are both set! Please only give one of the two.';
        }

        return false;
    }

    public function checkTopic($participation)
    {
        if (isset($participation['topicOther']) && $participation['topicOther'] == 'OTHER' && !isset($participation['topicOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesTopicOther is not set!';
        }

        return false;
    }

    public function checkApplication($participation)
    {
        if (isset($participation['application']) && $participation['application'] == 'OTHER' && !isset($participation['applicationOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesApplicationOther is not set!';
        }

        return false;
    }

    public function checkLevel($participation)
    {
        if (isset($participation['level']) && $participation['level'] == 'OTHER' && !isset($participation['levelOther'])) {
            $result['errorMessage'] = 'Invalid request, outComesLevelOther is not set!';
        }

        return false;
    }

    public function checkAanbiederUrl($aanbiederUrl)
    {
        if (isset($aanbiederUrl) and !$this->commonGroundService->isResource($aanbiederUrl)) {
            $result['errorMessage'] = 'Invalid request, aanbiederId is not an existing cc/organization!';
        }

        return false;
    }

    public function checkParticipationId($participationId)
    {
        if (isset($participationId) and !$this->eavService->hasEavObject(null, 'participations', $participationId)) {
            $result['errorMessage'] = 'Invalid request, participationId is not an existing eav/participation!';
        }

        return false;
    }

    public function checkLearningNeedId($learningNeedId)
    {
        if (isset($learningNeedId) && !$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            $result['errorMessage'] = 'Invalid request, learningNeedId is not an existing eav/learning_need!';
        }

        return false;
    }

    public function checkParticipationValuesPresenceStartDate($participation)
    {
        if (isset($participation['startDate']) && isset($participation['endDate'])) {
            if ($participation['startDate'] instanceof \DateTime && $participation['endDate'] instanceof \DateTime) {
                $startDate = $participation['startDate'];
                $participation['startDate'] = $startDate->format('Y-m-d H:i:s');
                $endDate = $participation['endDate'];
                $participation['endDate'] = $endDate->format('Y-m-d H:i:s');
            } else {
                $startDate = new \DateTime($participation['startDate']);
                $startDate->format('Y-m-d H:i:s');
                $endDate = new \DateTime($participation['endDate']);
                $endDate->format('Y-m-d H:i:s');
            }
            if ($startDate >= $endDate) {
                $result['errorMessage'] = 'Invalid request, detailsEndDate needs to be later than detailsStartDate!';
            }
        }
    }

    public function checkParticipationValuesStartDate($participation)
    {
        if (isset($participation['presenceStartDate']) && isset($participation['presenceEndDate'])) {
            if ($participation['presenceStartDate'] instanceof \DateTime && $participation['presenceEndDate'] instanceof \DateTime) {
                $startDate = $participation['presenceStartDate'];
                $participation['presenceStartDate'] = $startDate->format('Y-m-d H:i:s');
                $endDate = $participation['presenceEndDate'];
                $participation['presenceEndDate'] = $endDate->format('Y-m-d H:i:s');
            } else {
                $startDate = new \DateTime($participation['presenceStartDate']);
                $startDate->format('Y-m-d H:i:s');
                $endDate = new \DateTime($participation['presenceEndDate']);
                $endDate->format('Y-m-d H:i:s');
            }
            if ($startDate >= $endDate) {
                $result['errorMessage'] = 'Invalid request, presenceEndDate needs to be later than presenceStartDate!';
            }
        }
    }

    public function handleResult($participation, $learningNeedId = null)
    {
        // Put together the expected result for Lifely:
        $resource = new Participation();
        // For some reason setting the id does not work correctly when done inside this function, so do it after calling this handleResult function instead!
//        $resource->setId(Uuid::getFactory()->fromString($participation['id']));
        if (isset($participation['status'])) {
            $resource->setStatus($participation['status']);
        }
        $resource->setAanbiederId('/providers/'.$participation['aanbiederId']);
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
        $resource->setPresenceEndParticipationReason($participation['presenceEndParticipationReason']);
        $resource->setDetailsEngagements($participation['engagements']);
        $resource->setPresenceEngagements($participation['presenceEngagements']);

        //handle dates
        $this->handleParticipationDates($resource, $participation);

        if (isset($learningNeedId)) {
            $resource->setLearningNeedId($learningNeedId);
        }
        $this->entityManager->persist($resource);
        $resource->setId(Uuid::getFactory()->fromString($participation['id']));
        $this->entityManager->persist($resource);

        return $resource;
    }

    public function handleParticipationDates($resource, $participation)
    {
        if (isset($participation['startDate'])) {
            $resource->setDetailsStartDate(new \DateTime($participation['startDate']));
        }
        if (isset($participation['endDate'])) {
            $resource->setDetailsEndDate(new \DateTime($participation['endDate']));
        }

        if (isset($participation['presenceStartDate'])) {
            $resource->setPresenceStartDate(new \DateTime($participation['presenceStartDate']));
        }
        if (isset($participation['presenceEndDate'])) {
            $resource->setPresenceEndDate(new \DateTime($participation['presenceEndDate']));
        }

        return $participation;
    }

    public function handleResultJson($participation, $learningNeedId = null)
    {
        $resource['id'] = '/participations/'.$participation['id'];
        if (isset($participation['status'])) {
            $resource['status'] = $participation['status'];
        }
        $resource = [
            'aanbiederId'                     => $participation['aanbiederId'],
            'aanbiederName'                   => $participation['aanbiederName'],
            'aanbiederNote'                   => $participation['aanbiederNote'],
            'offerName'                       => $participation['offerName'],
            'offerCourse'                     => $participation['offerCourse'],
            'outComesGoal'                    => $participation['goal'],
            'outComesTopic'                   => $participation['topic'],
            'outComesTopicOther'              => $participation['topicOther'],
            'outComesApplication'             => $participation['application'],
            'outComesApplicationOther'        => $participation['applicationOther'],
            'outComesLevel'                   => $participation['level'],
            'outComesLevelOther'              => $participation['levelOther'],
            'detailsIsFormal'                 => $participation['isFormal'],
            'detailsGroupFormation'           => $participation['groupFormation'],
            'detailsTotalClassHours'          => $participation['totalClassHours'],
            'detailsCertificateWillBeAwarded' => $participation['certificateWillBeAwarded'],
            'detailsStartDate'                => $participation['startDate'],
            'detailsEndDate'                  => $participation['endDate'],
            'detailsEngagements'              => $participation['engagements'],
            'presenceEngagements'             => $participation['presenceEngagements'],
            'presenceStartDate'               => $participation['presenceStartDate'],
            'presenceEndDate'                 => $participation['presenceEndDate'],
            'presenceEndParticipationReason'  => $participation['presenceEndParticipationReason'],
        ];

        if (isset($learningNeedId)) {
            $resource['learningNeedId'] = '/learning_needs/'.$learningNeedId;
        }

        return $resource;
    }

    public function updateParticipation($participation)
    {
        $updateParticipation['status'] = 'REFERRED';
        $updateParticipation['presenceEngagements'] = null;
        $updateParticipation['presenceStartDate'] = null;
        $updateParticipation['presenceEndDate'] = null;
        $updateParticipation['presenceEndParticipationReason'] = null;
        $participation = $this->eavService->saveObject($updateParticipation, 'participations', 'eav', $participation['@eav']);

        // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        $result['participation'] = $participation;

        return $this->handleResult($result['participation']);
    }
}
