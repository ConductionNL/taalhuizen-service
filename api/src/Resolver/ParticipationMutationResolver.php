<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Participation;
use App\Service\ParticipationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ParticipationMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private ParticipationService $participationService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, ParticipationService $participationService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->participationService = $participationService;
    }

    /**
     * @inheritDoc
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
//        var_dump($context['info']->operation->name->value);
//        var_dump($context['info']->variableValues);
//        var_dump(get_class($item));
        if (!$item instanceof Participation && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createParticipation':
                return $this->createParticipation($item);
            case 'updateParticipation':
                return $this->updateParticipation($context['info']->variableValues['input']);
            case 'removeParticipation':
                return $this->removeParticipation($context['info']->variableValues['input']);
            case 'addMentorToParticipation':
                return $this->addMentorToParticipation($context['info']->variableValues['input']);
            case 'removeMentorFromParticipation':
                return $this->removeMentorFromParticipation($context['info']->variableValues['input']);
            case 'addGroupParticipation':
                return $this->addGroupParticipation($context['info']->variableValues['input']);
            case 'updateGroupParticipation':
                return $this->updateGroupParticipation($context['info']->variableValues['input']);
            case 'removeGroupParticipation':
                return $this->removeGroupParticipation($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createParticipation(Participation $resource): Participation
    {
        $result['result'] = [];

        // If aanbiederId is set generate the url for it
        $aanbiederUrl = null;
        if ($resource->getAanbiederId()) {
            $aanbiederId = explode('/',$resource->getAanbiederId());
            if (is_array($aanbiederId)) {
                $aanbiederId = end($aanbiederId);
            }
            $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $aanbiederId]);
        }
        if ($resource->getLearningNeedId()) {
            $learningNeedId = explode('/',$resource->getLearningNeedId());
            if (is_array($learningNeedId)) {
                $learningNeedId = end($learningNeedId);
            }
        } else {
            throw new Exception('Invalid request, learningNeedId is not set!');
        }

        // Transform DTO info to participation body...
        $participation = $this->dtoToParticipation($resource);

        // Do some checks and error handling
        $result = array_merge($result, $this->participationService->checkParticipationValues($participation, $aanbiederUrl, $learningNeedId));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save Participation and connect eav/learningNeed to it
            $result = array_merge($result, $this->participationService->saveParticipation($result['participation'], $learningNeedId));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->participationService->handleResult($result['participation'], $resource->getLearningNeedId());
            $resourceResult->setId(Uuid::getFactory()->fromString($result['participation']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        return $resourceResult;
    }

    public function updateParticipation(array $input): Participation
    {
        $result['result'] = [];

        $participationId = explode('/',$input['id']);
        if (is_array($participationId)) {
            $participationId = end($participationId);
        }
        // If aanbiederId is set generate the url for it
        $aanbiederUrl = null;
        if (isset($input['aanbiederId'])) {
            $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $input['aanbiederId']]);
        }
        if (!isset($input['learningNeedId'])) {
            $input['learningNeedId'] = null;
        }

        // Transform input info to participation body...
        $participation = $this->inputToParticipation($input);

        // Do some checks and error handling
        $result = array_merge($result, $this->participationService->checkParticipationValues($participation, $aanbiederUrl, null, $participationId));

        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Save Participation
            $result = array_merge($result, $this->participationService->saveParticipation($result['participation'], null, $participationId));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->participationService->handleResult($result['participation'], $input['learningNeedId']);
            $resourceResult->setId(Uuid::getFactory()->fromString($result['participation']['id']));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function removeParticipation(array $participation): ?Participation
    {
        $result['result'] = [];

        if (isset($participation['id'])) {
            $participationId = explode('/',$participation['id']);
            if (is_array($participationId)) {
                $participationId = end($participationId);
            }
        } else {
            throw new Exception('No id was specified!');
        }

        $result = array_merge($result, $this->participationService->deleteParticipation($participationId));

        $result['result'] = False;
        if (isset($result['participation'])){
            $result['result'] = True;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        return null;
    }

    public function addMentorToParticipation(array $input): Participation
    {
        $result['result'] = [];

        $participationId = explode('/',$input['participationId']);
        if (is_array($participationId)) {
            $participationId = end($participationId);
        }
        $mentorId = explode('/',$input['aanbiederEmployeeId']);
        if (is_array($mentorId)) {
            $mentorId = end($mentorId);
        }
        $mentorUrl = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $mentorId]);

        // Do check if mentorUrl exists
        // todo: Maybe also check if this employee is an aanbieder employee?
        if (!$this->commonGroundService->isResource($mentorUrl)) {
            throw new Exception('Invalid request, aanbiederEmployeeId is not an existing mrc/employee!');
        }

        // Get the participation
        $result = array_merge($result, $this->participationService->getParticipation($participationId));
        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Add Mentor to Participation
            $result = array_merge($result, $this->participationService->addMentorToParticipation($mentorUrl, $result['participation']));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->participationService->handleResult($result['participation']);
            $resourceResult->setId(Uuid::getFactory()->fromString($participationId));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function removeMentorFromParticipation(array $input): Participation
    {
        $result['result'] = [];

        $participationId = explode('/',$input['participationId']);
        if (is_array($participationId)) {
            $participationId = end($participationId);
        }
        $mentorId = explode('/',$input['aanbiederEmployeeId']);
        if (is_array($mentorId)) {
            $mentorId = end($mentorId);
        }
        $mentorUrl = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $mentorId]);

        // Do check if mentorUrl exists
        // todo: Maybe also check if this employee is an aanbieder employee?
        if (!$this->commonGroundService->isResource($mentorUrl)) {
            throw new Exception('Invalid request, aanbiederEmployeeId is not an existing mrc/employee!');
        }

        // Get the participation
        $result = array_merge($result, $this->participationService->getParticipation($participationId));
        if (!isset($result['errorMessage'])) {
            // No errors so lets continue... to:
            // Remove Mentor from Participation
            $result = array_merge($result, $this->participationService->removeMentorFromParticipation($mentorUrl, $result['participation']));

            // Now put together the expected result in $result['result'] for Lifely:
            $resourceResult = $this->participationService->handleResult($result['participation']);
            $resourceResult->setId(Uuid::getFactory()->fromString($participationId));
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }
        $this->entityManager->persist($resourceResult);
        return $resourceResult;
    }

    public function addGroupParticipation(array $input): Participation
    {
        // TODO
        // placeholder:
        $output = new Participation();
        $output->setId(Uuid::getFactory()->fromString('uuidhier'));
        return $output;
    }

    public function updateGroupParticipation(array $input): Participation
    {
        // TODO
        // placeholder:
        $output = new Participation();
        $output->setId(Uuid::getFactory()->fromString('uuidhier'));
        return $output;
    }

    public function removeGroupParticipation(array $input): Participation
    {
        // TODO
        // placeholder:
        $output = new Participation();
        $output->setId(Uuid::getFactory()->fromString('uuidhier'));
        return $output;
    }

    private function dtoToParticipation(Participation $resource) {
        // Get all info from the dto for creating a Participation and return the body for this
        // note: everything is nullabel in the dto, but eav doesn't like values set to null
        if ($resource->getAanbiederId()) { $participation['aanbiederId'] = $resource->getAanbiederId(); }
        if ($resource->getAanbiederName()) { $participation['aanbiederName'] = $resource->getAanbiederName(); }
        if ($resource->getAanbiederNote()) { $participation['aanbiederNote'] = $resource->getAanbiederNote(); }
        if ($resource->getOfferName()) { $participation['offerName'] = $resource->getOfferName(); }
        if ($resource->getOfferCourse()) { $participation['offerCourse'] = $resource->getOfferCourse(); }
        if ($resource->getOutComesGoal()) { $participation['goal'] = $resource->getOutComesGoal(); }
        if ($resource->getOutComesTopic()) { $participation['topic'] = $resource->getOutComesTopic(); }
        if ($resource->getOutComesTopicOther()) { $participation['topicOther'] = $resource->getOutComesTopicOther(); }
        if ($resource->getOutComesApplication()) {  $participation['application'] = $resource->getOutComesApplication(); }
        if ($resource->getOutComesApplicationOther()) { $participation['applicationOther'] = $resource->getOutComesApplicationOther(); }
        if ($resource->getOutComesLevel()) { $participation['level'] = $resource->getOutComesLevel(); }
        if ($resource->getOutComesLevelOther()) { $participation['levelOther'] = $resource->getOutComesLevelOther(); }
        if (!is_null($resource->getDetailsIsFormal())) { $participation['isFormal'] = $resource->getDetailsIsFormal(); }
        if ($resource->getDetailsGroupFormation()) { $participation['groupFormation'] = $resource->getDetailsGroupFormation(); }
        if ($resource->getDetailsTotalClassHours()) { $participation['totalClassHours'] = $resource->getDetailsTotalClassHours(); }
        if (!is_null($resource->getDetailsCertificateWillBeAwarded())) { $participation['certificateWillBeAwarded'] = $resource->getDetailsCertificateWillBeAwarded(); }
        if ($resource->getDetailsStartDate()) { $participation['startDate'] = $resource->getDetailsStartDate()->format('d-m-Y H:i:s'); }
        if ($resource->getDetailsEndDate()) { $participation['endDate'] = $resource->getDetailsEndDate()->format('d-m-Y H:i:s'); }
        if ($resource->getDetailsEngagements()) { $participation['engagements'] = $resource->getDetailsEngagements(); }
        return $participation;
    }

    private function inputToParticipation(array $input) {
        // Get all info from the input array for updating a Participation and return the body for this
        // note: everything is nullabel in the dto, but eav doesn't like values set to null
        if (isset($input['aanbiederId'])) { $participation['aanbiederId'] = $input['aanbiederId']; }
        if (isset($input['aanbiederName'])) { $participation['aanbiederName'] = $input['aanbiederName']; }
        if (isset($input['aanbiederNote'])) { $participation['aanbiederNote'] = $input['aanbiederNote']; }
        if (isset($input['offerName'])) { $participation['offerName'] = $input['offerName']; }
        if (isset($input['offerCourse'])) { $participation['offerCourse'] = $input['offerCourse']; }
        if (isset($input['outComesGoal'])) { $participation['goal'] = $input['outComesGoal']; }
        if (isset($input['outComesTopic'])) { $participation['topic'] = $input['outComesTopic']; }
        if (isset($input['outComesTopicOther'])) { $participation['topicOther'] = $input['outComesTopicOther']; }
        if (isset($input['outComesApplication'])) {  $participation['application'] = $input['outComesApplication']; }
        if (isset($input['outComesApplicationOther'])) { $participation['applicationOther'] = $input['outComesApplicationOther']; }
        if (isset($input['outComesLevel'])) { $participation['level'] = $input['outComesLevel']; }
        if (isset($input['outComesLevelOther'])) { $participation['levelOther'] = $input['outComesLevelOther']; }
        if (isset($input['detailsIsFormal'])) { $participation['isFormal'] = $input['detailsIsFormal']; }
        if (isset($input['detailsGroupFormation'])) { $participation['groupFormation'] = $input['detailsGroupFormation']; }
        if (isset($input['detailsTotalClassHours'])) { $participation['totalClassHours'] = $input['detailsTotalClassHours']; }
        if (isset($input['detailsCertificateWillBeAwarded'])) { $participation['certificateWillBeAwarded'] = $input['detailsCertificateWillBeAwarded']; }
        if (isset($input['detailsStartDate'])) { $participation['startDate'] = $input['detailsStartDate']; }
        if (isset($input['detailsEndDate'])) { $participation['endDate'] = $input['detailsEndDate']; }
        if (isset($input['detailsEngagements'])) { $participation['engagements'] = $input['detailsEngagements']; }
        if (isset($input['presenceStartDate'])) { $participation['presenceStartDate'] = $input['presenceStartDate']; }
        if (isset($input['presenceEndDate'])) { $participation['presenceEndDate'] = $input['presenceEndDate']; }
        if (isset($input['presenceEndParticipationReason'])) { $participation['presenceEndParticipationReason'] = $input['presenceEndParticipationReason']; }
        return $participation;
    }
}
