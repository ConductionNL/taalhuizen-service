<?php

namespace App\Service;

use App\Entity\LearningNeed;
use App\Entity\Participation;
use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class NewParticipationService
{
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private EntityManagerInterface $entityManager;

    public function __construct(LayerService $layerService)
    {
        $this->commonGroundService = $layerService->commonGroundService;
        $this->eavService = new EAVService($this->commonGroundService);
        $this->entityManager = $layerService->entityManager;
    }

    public function persistParticipation(Participation $participation, array $arrays): Participation
    {
        $this->entityManager->persist($participation);
        $participation->setId(Uuid::fromString($arrays['participation']['id']));
        $this->entityManager->persist($participation);

        return $participation;
    }

    public function deleteParticipation($id): Response
    {
        if ($this->eavService->hasEavObject(null, 'participations', $id)) {
            // Get the learningNeed from EAV
            $participation = $this->eavService->getObject(['entityName' => 'participations', 'eavId' => $id]);

            // Delete the learningNeed in EAV
            $this->eavService->deleteObject($participation['eavId']);
        } else {
            throw new BadRequestPathException('Invalid request, '.$id.' is not an existing eav/participation!', 'participation');
        }

        return new Response(null, 204);
    }

    public function updateParticipation(array $participation, string $participationId): ArrayCollection
    {
        $learningNeed = $this->eavService->saveObject($participation, ['entityName' => 'participations', 'eavId' => $participationId]);

        return new ArrayCollection($learningNeed);
    }

    public function createParticipation(Participation $participation): Participation
    {
        $this->checkParticipation($participation);

        $array = [
            'aanbiederId'              => $participation->getProviderId() ?? null,
            'aanbiederName'            => $participation->getProviderName() ?? null,
            'aanbiederNote'            => $participation->getProviderNote() ?? null,
            'offerName'                => $participation->getOfferName() ?? null,
            'offerCourse'              => $participation->getOfferCourse() ?? null,
            'learningNeedOutCome'      => $participation->getLearningNeedOutCome() ?? null,
            'isFormal'                 => $participation->getIsFormal() ?? null,
            'groupFormation'           => $participation->getGroupFormation(),
            'totalClassHours'          => $participation->getTotalClassHours() ?? null,
            'certificateWillBeAwarded' => $participation->getCertificateWillBeAwarded() ?? null,
            'startDate'                => $participation->getStartDate()->format('Y-m-d H:i:s') ?? null,
            'endDate'                  => $participation->getEndDate()->format('Y-m-d H:i:s') ?? null,
            'engagements'              => $participation->getEngagements() ?? null,
        ];

        $arrays['participation'] = $this->eavService->saveObject(array_filter($array), ['entityName' => 'participations']);
        $arrays['participation'] = $this->addLearningNeedToParticipation($participation->getLearningNeedId(), $arrays['participation']);

        if ($participation->getProviderId() !== null) {
            $arrays['participation'] = $this->addProviderToParticipation($participation->getProviderId(), $arrays['participation']);
        }

        $arrays['participation'] = $this->updateParticipationStatus($arrays['participation']);

        return $this->persistParticipation($participation, $arrays);
    }

    /**
     * This function updates the eav/participations status.
     *
     * @param array $participation Array with data from the eav/participation
     *
     * @throws Exception
     *
     * @return array A eav/participation is returned from EAV
     */
    public function updateParticipationStatus(array $participation): array
    {

        // Check what the current status should be
        $updateParticipation = $this->handleParticipationStatus($participation);
        // Check if the status needs to be changed
        if ($participation['status'] != $updateParticipation['status']) {
            // Update status
            $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participation['@eav']]);
        }

        return $participation;
    }

    /**
     * This function handles the eav/participations status.
     *
     * @param array $participation Array with data from the eav/participation
     *
     * @throws Exception
     *
     * @return array A eav/participation - status is returned.
     */
    public function handleParticipationStatus(array $participation): array
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
        } elseif (isset($participation['providerName'])) {
            $updateParticipation['status'] = 'ACTIVE';
        } else {
            $updateParticipation['status'] = 'REFERRED';
        }

        return $updateParticipation;
    }

    /**
     * This function adds a cc/organizations to a eav/participation with the given providerId and participation.
     *
     * @param string $providerId    Id of the cc/organization
     * @param array  $participation Array with data from the eav/participation
     *
     * @return array A eav/participation is returned from the EAV
     */
    private function addProviderToParticipation(string $providerId, array $participation): array
    {
        $result = [];
        $providerUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $providerId]);
        // should already be checked but just in case:
        if (!$this->commonGroundService->isResource($providerUrl)) {
            $result['errorMessage'] = 'Invalid request, providerId is not an existing cc/organization!';
        }

        // Check if aanbieder already has an EAV object
        if ($this->eavService->hasEavObject($providerUrl)) {
            $getOrganization = $this->eavService->getObject(['entityName' => 'organizations', 'componentCode' => 'cc', 'self' => $providerUrl]);
            $organization['participations'] = $getOrganization['participations'];
        }
        if (!isset($organization['participations'])) {
            $organization['participations'] = [];
        }

        // Connect the organization in EAV to the EAV/participation
        if (!in_array($participation['@eav'], $organization['participations'])) {
            array_push($organization['participations'], $participation['@eav']);
            $organization = $this->eavService->saveObject($organization, ['entityName' => 'organizations', 'componentCode' => 'cc', 'self' => $providerUrl]);

            // Add $organization to the $result['organization'] because this is convenient when testing or debugging (mostly for us)
            $result['organization'] = $organization;

            // Update the participation to add the cc/organization to it
            $updateParticipation['aanbieder'] = $this->commonGroundService->getResource($this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $organization['id']]));
            $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participation['@eav']]);

            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $participation;
    }

    /**
     * This function adds a eav/learningNeeds to a eav/participation with the given learningNeedId and participation.
     *
     * @param string $learningNeedId Id of the eav/learningNeed
     * @param array  $participation  Array with data from the eav/participation
     *
     * @throws Exception
     *
     * @return array A eav/participation is returned from the EAV
     */
    private function addLearningNeedToParticipation(string $learningNeedId, array $participation): array
    {
        $result = [];
        // should already be checked but just in case:
        if (!$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            throw new Exception('Invalid request, learningNeedId is not an existing eav/learning_need!');
        }
        $getLearningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'eavId' => $learningNeedId]);
        if (isset($getLearningNeed['participations'])) {
            $learningNeed['participations'] = $getLearningNeed['participations'];
        }
        if (!isset($learningNeed['participations'])) {
            $learningNeed['participations'] = [];
        }

        // Connect the learningNeed in EAV to the EAV/participation
        if (!in_array($participation['@eav'], $learningNeed['participations'])) {
            array_push($learningNeed['participations'], $participation['@eav']);
            $learningNeed = $this->eavService->saveObject($learningNeed, ['entityName' => 'learning_needs', 'eavId' => $learningNeedId]);

            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['learningNeed'] = $learningNeed;

            // Update the participation to add the EAV/learningNeed to it
            $updateParticipation['learningNeed'] = $learningNeed['@eav'];
            $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participation['@eav']]);

            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $participation;
    }

    public function checkParticipation(Participation $participation): void
    {
        if ($participation->getProviderId() == null && $participation->getProviderName() == null && $participation->getProviderNote() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'provider');
        }
        if ($participation->getOfferCourse() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'offer course');
        }
        if ($participation->getGroupFormation() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'group formation');
        }
        if ($participation->getLearningNeedId() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'learning need id');
        }

        if ($participation->getProviderId() != null && $participation->getProviderNote() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'provider note');
        }

        if ($participation->getProviderNote() != null && $participation->getProviderId() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'provider id');
        }

        if ($participation->getProviderId() != null) {
            $providerUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $participation->getProviderId()]);
            if (!$this->commonGroundService->isResource($providerUrl)) {
                throw new BadRequestPathException('Unable to find valid provider with provided id.', 'provider');
            }
        }
    }
}
