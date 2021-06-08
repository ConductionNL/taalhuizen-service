<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Participation;
use App\Service\ParticipationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ParticipationMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private ParticipationService $participationService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, ParticipationService $participationService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->participationService = $participationService;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Participation && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createParticipation':
                return $this->createParticipation($item);
            case 'updateParticipation':
                return $this->updateParticipation($context['info']->variableValues['input']);
            case 'removeParticipation':
                return $this->removeParticipation($context['info']->variableValues['input']);
            case 'updateMentorParticipation':
                return $this->updateMentorGroupParticipation($context['info']->variableValues['input'], 'mentor');
            case 'removeMentorFromParticipation':
                return $this->removeMentorFromParticipation($context['info']->variableValues['input']);
            case 'addGroupToParticipation':
                return $this->addGroupToParticipation($context['info']->variableValues['input']);
            case 'updateGroupParticipation':
                return $this->updateMentorGroupParticipation($context['info']->variableValues['input'], 'group');
            case 'removeGroupFromParticipation':
                return $this->removeGroupFromParticipation($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createParticipation(Participation $resource): Participation
    {
        $result['result'] = [];
        if ($resource->getAanbiederId()) {
          $aanbiederId = $this->setAanbiederId(null, $resource);
          $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $aanbiederId]);
        }
        if ($resource->getLearningNeedId()) {
            $learningNeedId = $this->setLearningneedId($resource);
        }

        // Transform DTO info to participation body...
        $participation = $this->dtoToParticipation($resource, $aanbiederId);

        // Do some checks and error handling
        $result = array_merge($result, $this->participationService->checkParticipationValues($participation, $aanbiederUrl, $learningNeedId));

        return $this->participationService->saveParticipation($result['participation'], $learningNeedId);
    }

    public function updateParticipation(array $input): Participation
    {
        $result['result'] = [];

        $participationId = $this->setParticipationId($input);
        $aanbiederId = $this->setAanbiederId($input);

        // If aanbiederId is set generate the url for it
        $aanbiederUrl = null;
        if (isset($aanbiederId)) {
            $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $aanbiederId]);
        }

        // Transform input info to participation body...
        $participation = $this->inputToParticipation($input);
        // Do some checks and error handling
        $result = array_merge($result, $this->participationService->checkParticipationValues($participation, $aanbiederUrl, null, $participationId));

        return $this->participationService->saveParticipation($result['participation'], null, $participationId);
    }

    public function removeParticipation(array $participation): ?Participation
    {
        $result['result'] = [];
        $participationId = $this->setParticipationId($participation);

        $result = array_merge($result, $this->participationService->deleteParticipation($participationId));

        $result['result'] = false;
        if (isset($result['participation'])) {
            $result['result'] = true;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return null;
    }

    public function removeMentorFromParticipation(array $input): Participation
    {
        $mentorUrl = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $this->setMentorId($input)]);

        $result = $this->getParticipationMentor($input);

        return $this->participationService->removeMentorFromParticipation($mentorUrl, $result['participation']);
    }

    public function getParticipationMentor(array $input)
    {
        $result['result'] = [];

        $participationId = $this->setParticipationId($input);
        $mentorId = $this->setMentorId($input);
        $mentorUrl = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $mentorId]);

        // Do check if mentorUrl exists
        // todo: Maybe also check if this employee is an aanbieder employee?
        if (!$this->commonGroundService->isResource($mentorUrl)) {
            throw new Exception('Invalid request, aanbiederEmployeeId is not an existing mrc/employee!');
        }

        // Get the participation
        $result = array_merge($result, $this->participationService->getParticipation($participationId));

        return $result;
    }

    public function setMentorId(array $input)
    {
        $mentorId = explode('/', $input['aanbiederEmployeeId']);
        $mentorId = $this->isArray($mentorId);

        return $mentorId;
    }

    public function setLearningneedId(Participation $resource)
    {
        $learningNeedId = explode('/', $resource->getLearningNeedId());
        $learningNeedId = $this->isArray($learningNeedId);

        return $learningNeedId;
    }

    public function setParticipationId(array $input)
    {
        $participationId = explode('/', $input['participationId']);
        $participationId = $this->isArray($participationId);

        return $participationId;
    }

    public function setAanbiederId(array $input, ?Participation $resource = null)
    {
        if ($resource){
            $aanbiederId = explode('/', $resource->setAanbiederId());
        } else {
            $aanbiederId = explode('/', $input['aanbiederId']);
        }
        $aanbiederId = $this->isArray($aanbiederId);

        return $aanbiederId;
    }

    public function setGroupId(array $input)
    {
        $groupId = explode('/', $input['groupId']);
        $groupId = $this->isArray($groupId);

        return $groupId;
    }

    public function isArray(string $id)
    {
        if (is_array($id)) {
            $id = end($id);
        }

        return $id;
    }

    public function addGroupToParticipation(array $input): Participation
    {
        $groupUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $this->setGroupId($input)]);

        $result = $this->getParticipationGroup($input);

        return $this->participationService->addGroupToParticipation($groupUrl, $result['participation']);
    }

    public function removeGroupFromParticipation(array $input): Participation
    {
        $groupUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $this->setGroupId($input)]);

        $result = $this->getParticipationGroup($input);

        return $this->participationService->removeGroupFromParticipation($groupUrl, $result['participation']);
    }

    public function getParticipationGroup(array $input)
    {
        $result['result'] = [];

        $participationId = $this->setParticipationId($input);
        $groupId = $this->setGroupId($input);
        $groupUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'groups', 'id' => $groupId]);

        // Do check if groupUrl exists
        if (!$this->commonGroundService->isResource($groupUrl)) {
            throw new Exception('Invalid request, groupId is not an existing edu/group!');
        }

        // Get the participation
        $result = array_merge($result, $this->participationService->getParticipation($participationId));

        return $result;
    }

    public function updateMentorGroupParticipation(array $input, $type): Participation
    {
        $result['result'] = [];

        $participationId = $this->setParticipationId($input);

        // check for valid datetimes
        $this->checkDateTimes($input);
        $this->checkPresenceEndDate($input);
        $this->checkValidEnums($input);

        // Do some checks and error handling
        $result = array_merge($result, $this->participationService->checkParticipationValues($input, null, null, $participationId));

        // Make sure this participation actually has a mentor/group connected to it
        $checkParticipation = $this->participationService->getParticipation($participationId);

        if (!isset($checkParticipation['participation'][$type])) {
            throw new Exception('Warning, this participation has no '.$type.'!');
        }

        return $this->participationService->saveParticipation($result['participation'], null, $participationId);
    }

    private function checkDateTimes(array $input)
    {
        if (isset($input['presenceStartDate'])) {
            try {
                new \DateTime($input['presenceStartDate']);
            } catch (Exception $e) {
                throw new Exception('presenceStartDate: Failed to parse string to DateTime.');
            }
        }
    }

    private function checkPresenceEndDate(array $input)
    {
        if (isset($input['presenceEndDate'])) {
            try {
                new \DateTime($input['presenceEndDate']);
            } catch (Exception $e) {
                throw new Exception('presenceEndDate: Failed to parse string to DateTime.');
            }
        }
    }

    private function checkValidEnums(array $input)
    {
        // check for valid enum
        if (isset($input['presenceEndParticipationReason'])) {
            $presenceEndParticipationReasonEnum = ['MOVED', 'JOB', 'ILLNESS', 'DEATH', 'COMPLETED_SUCCESSFULLY', 'FAMILY_CIRCUMSTANCES', 'DOES_NOT_MEET_EXPECTATIONS', 'OTHER'];
            if (!in_array($input['presenceEndParticipationReason'], $presenceEndParticipationReasonEnum)) {
                throw new Exception('presenceEndParticipationReason: The selected value is not a valid option.');
            }
        }
    }

    private function dtoToParticipation(Participation $resource, $aanbiederId)
    {
        // Get all info from the dto for creating a Participation and return the body for this
        return [
            'aanbiederId'              => $resource->getAanbiederId() ? $aanbiederId : null,
            'aanbiederName'            => $resource->getAanbiederName() ?? null,
            'aanbiederNote'            => $resource->getAanbiederNote() ?? null,
            'offerName'                => $resource->getOfferName() ?? null,
            'offerCourse'              => $resource->getOfferCourse() ?? null,
            'goal'                     => $resource->getOutComesGoal() ?? null,
            'topic'                    => $resource->getOutComesTopic() ?? null,
            'topicOther'               => $resource->getOutComesTopicOther() ?? null,
            'application'              => $resource->getOutComesApplication() ?? null,
            'applicationOther'         => $resource->getOutComesApplicationOther() ?? null,
            'level'                    => $resource->getOutComesLevel() ?? null,
            'levelOther'               => $resource->getOutComesLevelOther() ?? null,
            'isFormal'                 => $resource->getDetailsIsFormal() ?? null,
            'groupFormation'           => $resource->getDetailsGroupFormation() ?? null,
            'totalClassHours'          => $resource->getDetailsTotalClassHours() ?? null,
            'certificateWillBeAwarded' => $resource->getDetailsCertificateWillBeAwarded() ?? null,
            'startDate'                => $resource->getDetailsStartDate() ?? null,
            'endDate'                  => $resource->getDetailsEndDate() ?? null,
            'engagements'              => $resource->getDetailsEngagements() ?? null,
        ];
    }

    private function inputToParticipation(array $input)
    {
        // Get all info from the input array for updating a Participation and return the body for this
        return [
            'aanbiederId'                    => $input['aanbiederId'] ?? null,
            'aanbiederName'                  => $input['aanbiederName'] ?? null,
            'aanbiederNote'                  => $input['aanbiederNote'] ?? null,
            'offerName'                      => $input['offerName'] ?? null,
            'offerCourse'                    => $input['offerCourse'] ?? null,
            'goal'                           => $input['outComesGoal'] ?? null,
            'topic'                          => $input['outComesTopic'] ?? null,
            'topicOther'                     => $input['outComesTopicOther'] ?? null,
            'application'                    => $input['outComesApplication'] ?? null,
            'applicationOther'               => $input['outComesApplicationOther'] ?? null,
            'level'                          => $input['outComesLevel'] ?? null,
            'levelOther'                     => $input['outComesLevelOther'] ?? null,
            'isFormal'                       => $input['detailsIsFormal'] ?? null,
            'groupFormation'                 => $input['detailsGroupFormation'] ?? null,
            'totalClassHours'                => $input['detailsTotalClassHours'] ?? null,
            'certificateWillBeAwarded'       => $input['detailsCertificateWillBeAwarded'] ?? null,
            'startDate'                      => $input['detailsStartDate'] ?? null,
            'endDate'                        => $input['detailsEndDate'] ?? null,
            'engagements'                    => $input['detailsEngagements'] ?? null,
            'presenceStartDate'              => $input['presenceStartDate'] ?? null,
            'presenceEndDate'                => $input['presenceEndDate'] ?? null,
            'presenceEndParticipationReason' => $input['presenceEndParticipationReason'] ?? null,
        ];
    }
}
