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
            $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $resource->getAanbiederId()]);
        }
        $learningNeedId = null;
        if ($resource->getLearningNeedId()) {
            $learningNeedId = explode('/',$resource->getLearningNeedId());
            if (is_array($learningNeedId)) {
                $learningNeedId = end($learningNeedId);
            }
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

        // Transform input info to participation body...
        $participation = $this->inputToParticipation($input);

        //todo:
//        // Do some checks and error handling
//        $result = array_merge($result, $this->learningNeedService->checkLearningNeedValues($learningNeed, null, $learningNeedId));
//
//        if (!isset($result['errorMessage'])) {
//            // No errors so lets continue... to:
//            // Save LearningNeed and connect student/participant to it
//            $result = array_merge($result, $this->learningNeedService->saveLearningNeed($result['learningNeed'], null, $learningNeedId));
//
//            // Now put together the expected result in $result['result'] for Lifely:
//            $resourceResult = $this->learningNeedService->handleResult($result['learningNeed'], $input['studentId']);
//            $resourceResult->setId(Uuid::getFactory()->fromString($result['learningNeed']['id']));
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
//        $this->entityManager->persist($resourceResult);
//        return $resourceResult;
        return new Participation();
    }

    public function removeParticipation(array $participation): ?Participation
    {
        //todo:
//        $result['result'] = [];
//
//        // If learningNeedUrl or learningNeedId is set generate the id for it, needed for eav calls later
//        $learningNeedId = null;
//        if (isset($learningNeed['learningNeedUrl'])) {
//            $learningNeedId = $this->commonGroundService->getUuidFromUrl($learningNeed['learningNeedUrl']);
//        } elseif (isset($learningNeed['id'])) {
//            $learningNeedId = explode('/',$learningNeed['id']);
//            if (is_array($learningNeedId)) {
//                $learningNeedId = end($learningNeedId);
//            }
//        } else {
//            throw new Exception('No learningNeedUrl or id was specified');
//        }
//
//        $result = array_merge($result, $this->learningNeedService->deleteLearningNeed($learningNeedId));
//
//        $result['result'] = False;
//        if (isset($result['learningNeed'])){
//            $result['result'] = True;
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
        return null;
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
        $participation['aanbiederId'] = $input['aanbiederId'];

        // todo:
//        $participation['description'] = $input['learningNeedDescription'];
//        $participation['motivation'] = $input['learningNeedMotivation'];
//        $participation['goal'] = $input['desiredOutComesGoal'];
//        $participation['topic'] = $input['desiredOutComesTopic'];
//        if (isset($input['desiredOutComesTopicOther'])) {
//            $participation['topicOther'] = $input['desiredOutComesTopicOther'];
//        }
//        $participation['application'] = $input['desiredOutComesApplication'];
//        if (isset($input['desiredOutComesApplicationOther'])) {
//            $participation['applicationOther'] = $input['desiredOutComesApplicationOther'];
//        }
//        $participation['level'] = $input['desiredOutComesLevel'];
//        if (isset($input['desiredOutComesLevelOther'])) {
//            $participation['levelOther'] = $input['desiredOutComesLevelOther'];
//        }
//        $participation['desiredOffer'] = $input['offerDesiredOffer'];
//        $participation['advisedOffer'] = $input['offerAdvisedOffer'];
//        $participation['offerDifference'] = $input['offerDifference'];
//        if (isset($input['offerDifferenceOther'])) {
//            $participation['offerDifferenceOther'] = $input['offerDifferenceOther'];
//        }
//        if (isset($input['offerEngagements'])) {
//            $participation['offerEngagements'] = $input['offerEngagements'];
//        }
        return $participation;
    }
}
