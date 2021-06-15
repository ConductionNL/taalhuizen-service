<?php

namespace App\Service;

use App\Entity\LearningNeed;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class LearningNeedService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private ParticipationService $participationService;

    /**
     * LearningNeedService constructor.
     *
     * @param ParticipationService   $participationService
     * @param LayerService           $layerService
     */
    public function __construct(ParticipationService $participationService, LayerService $layerService)
    {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->eavService = new EAVService($layerService->commonGroundService);
        $this->participationService = $participationService;
    }

    /**
     * Saves a learningNeed in eav-component using the eavService.
     *
     * @param array       $learningNeed   the body of the learningNeed.
     * @param string|null $studentUrl     the url to the student (edu/participant) this learningNeed is for. In order to connect them.
     * @param string|null $learningNeedId the id of a already existing learningNeed for updating it.
     *
     * @throws Exception
     *
     * @return array the created or update learningNeed.
     */
    public function saveLearningNeed(array $learningNeed, string $studentUrl = null, string $learningNeedId = null): array
    {
        $now = new DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now = $now->format('d-m-Y H:i:s');

        // Save the learningNeed in EAV
        if (isset($learningNeedId)) {
            // Update
            $learningNeed['dateModified'] = $now;
            $learningNeed = $this->eavService->saveObject($learningNeed, ['entityName' => 'learning_needs', 'eavId' => $learningNeedId]);
        } else {
            // Create
            $learningNeed['dateCreated'] = $now;
            $learningNeed['dateModified'] = $now;
            $learningNeed = $this->eavService->saveObject($learningNeed, ['entityName' => 'learning_needs']);
        }

        // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        $result['learningNeed'] = $learningNeed;

        // Save the participant in EAV with the EAV/learningNeed connected to it
        if (isset($studentUrl)) {
            $result = array_merge($result, $this->addStudentToLearningNeed($studentUrl, $learningNeed));
        }

        return $result;
    }

    /**
     * This function creates and returns a body for an (student/) edu/participant with a learningNeeds array in it used to save this in the eav-component.
     * If the given studentUrl already has an eav object the learningNeeds connected to this student will be returned in this array.
     *
     * @param string $studentUrl the edu/participant url of a student.
     *
     * @throws Exception
     *
     * @return array the body for an (student/) edu/participant with a learningNeeds array in it.
     */
    public function handleParticipantLearningNeeds(string $studentUrl): array
    {
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
            $participant['learningNeeds'] = $getParticipant['learningNeeds'];
        }
        if (!isset($participant['learningNeeds'])) {
            $participant['learningNeeds'] = [];
        }

        return $participant;
    }

    /**
     * This function connects a student and a learningNeed with the use of the eav-component/eavService.
     *
     * @param string $studentUrl   the edu/participant url of a student.
     * @param array  $learningNeed the body of the learningNeed.
     *
     * @throws Exception
     *
     * @return array a result array with the participant and learningNeed in it.
     */
    public function addStudentToLearningNeed(string $studentUrl, array $learningNeed): array
    {
        $result = [];
        // Check if student already has an EAV object
        $participant = $this->handleParticipantLearningNeeds($studentUrl);

        // Save the participant in EAV with the EAV/learningNeed connected to it
        if (!in_array($learningNeed['@eav'], $participant['learningNeeds'])) {
            array_push($participant['learningNeeds'], $learningNeed['@eav']);
            $participant = $this->eavService->saveObject($participant, ['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);

            // Add $participant to the $result['participant'] because this is convenient when testing or debugging (mostly for us)
            $result['participant'] = $participant;

            // Update the learningNeed to add the EAV/edu/participant to it
            if (isset($learningNeed['participants'])) {
                $updateLearningNeed['participants'] = $learningNeed['participants'];
            }
            if (!isset($updateLearningNeed['participants'])) {
                $updateLearningNeed['participants'] = [];
            }
            if (!in_array($participant['@id'], $updateLearningNeed['participants'])) {
                array_push($updateLearningNeed['participants'], $participant['@id']);
                $learningNeed = $this->eavService->saveObject($updateLearningNeed, ['entityName' => 'learning_needs', 'self' => $learningNeed['@eav']]);

                // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
                $result['learningNeed'] = $learningNeed;
            }
        }

        return $result;
    }

    /**
     * This function removes all participants from an eav/edu/learningNeed and add the urls of the removed objects to the given result array.
     *
     * @param array $learningNeed the body of the learningNeed.
     * @param array $result       the result to which the urls of the removed edu/participants will be added.
     *
     * @throws Exception
     *
     * @return array the result array with all participant urls that got removed.
     */
    public function removeParticipantsFromLearningNeed(array $learningNeed, array $result): array
    {
        if (isset($learningNeed['participants'])) {
            foreach ($learningNeed['participants'] as $studentUrl) {
                $studentResult = $this->removeLearningNeedFromStudent($learningNeed['@eav'], $studentUrl);
                if (isset($studentResult['participant'])) {
                    // Add $studentUrl to the $result['participants'] because this is convenient when testing or debugging (mostly for us)
                    array_push($result['participants'], $studentResult['participant']['@id']);
                }
            }
        }

        return $result;
    }

    /**
     * Deletes all participations of a learningNeed using the participationService->deleteParticipation function.
     *
     * @param array $learningNeed the body of a learningNeed.
     *
     * @throws Exception
     */
    public function deleteLearningNeedParticipations(array $learningNeed): void
    {
        if (isset($learningNeed['participations'])) {
            foreach ($learningNeed['participations'] as $participationUrl) {
                $this->participationService->deleteParticipation(null, $participationUrl, true);
            }
        }
    }

    /**
     * Deletes a learningNeed with the given id.
     * This also deletes any connected Participations and the connection between this learningNeed and the student (edu/participant) in the eav-component.
     *
     * @param string $id the id of the learningNeed you want to delete.
     *
     * @throws Exception
     *
     * @return array the result array with info of the delete learningNeed an the participant(s) of this learningNeed. Or an errorMessage.
     */
    public function deleteLearningNeed(string $id): array
    {
        if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
            $result['participants'] = [];
            // Get the learningNeed from EAV
            $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'eavId' => $id]);

            // Remove this learningNeed from all EAV/edu/participants
            $result = $this->removeParticipantsFromLearningNeed($learningNeed, $result);

            $this->deleteLearningNeedParticipations($learningNeed);

            // Delete the learningNeed in EAV
            $this->eavService->deleteObject($learningNeed['eavId']);
            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['learningNeed'] = $learningNeed;
        } else {
            $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/learning_need!';
        }

        return $result;
    }

    /**
     * This function removes a learningNeed from a student (edu/participant) in the eav-component with the EAVService.
     *
     * @param string $learningNeedUrl the learningNeed url that will be removed from this student.
     * @param string $studentUrl      the edu/participant url of the student.
     *
     * @throws Exception
     *
     * @return array the result array with the edu/participant in it of which the learningNeed got removed.
     */
    public function removeLearningNeedFromStudent(string $learningNeedUrl, string $studentUrl): array
    {
        $result = [];
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
            if (isset($getParticipant['learningNeeds'])) {
                $participant['learningNeeds'] = array_values(array_filter($getParticipant['learningNeeds'], function ($participantLearningNeed) use ($learningNeedUrl) {
                    return $participantLearningNeed != $learningNeedUrl;
                }));
                $result['participant'] = $this->eavService->saveObject($participant, ['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
            }
        }
        // only works when learningNeed is deleted after, because relation is not removed from the EAV learningNeed object in here
        return $result;
    }

    /**
     * This function gets and returns a learningNeed object from the eav-component with the EAVService.
     * It is recommended to use the id, but can also be used with an url instead.
     *
     * @param string      $id  the id of the learningNeed (eav).
     * @param string|null $url an url of the learningNeed (eav url).
     *
     * @throws Exception
     *
     * @return array the result array containing the learningNeed or an errorMessage.
     */
    public function getLearningNeed(string $id, string $url = null): array
    {
        $result = [];
        // Get the learningNeed from EAV and add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
                $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'eavId' => $id]);
                $result['learningNeed'] = $learningNeed;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/learning_need!';
            }
        } elseif (isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'self' => $url]);
                $result['learningNeed'] = $learningNeed;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$url.' is not an existing eav/learning_need!';
            }
        }

        return $result;
    }

    /**
     * This function gets and returns all learningNeeds from a student, from the eav-component using the EAVService.
     * Can be used with dateFrom and dateUntil to get all learningNeeds created, after, before or between two dates.
     *
     * @param string      $studentId the id of a student (edu/participant) to get all learningNeeds from.
     * @param string|null $dateFrom  a DateTime string.
     * @param string|null $dateUntil a DateTime string.
     *
     * @throws Exception
     *
     * @return array the result array containing the learningNeeds or an message/errorMessage.
     */
    public function getLearningNeeds(string $studentId, string $dateFrom = null, string $dateUntil = null): array
    {
        // Get the eav/edu/participant learningNeeds from EAV and add the $learningNeeds @id's to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if ($this->eavService->hasEavObject(null, 'participants', $studentId, 'edu')) {
            $result['learningNeeds'] = [];
            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $studentId]);
            $participant = $this->eavService->getObject(['entityName' => 'participants', 'componentCode' => 'edu', 'self' => $studentUrl]);
            if (isset($participant['learningNeeds'])) {
                if (isset($dateFrom)) {
                    $dateFrom = new DateTime($dateFrom);
                    $dateFrom->format('Y-m-d H:i:s');
                }
                if (isset($dateUntil)) {
                    $dateUntil = new DateTime($dateUntil);
                    $dateUntil->format('Y-m-d H:i:s');
                }
                foreach ($participant['learningNeeds'] as $learningNeedUrl) {
                    $result = $this->getStudentLearningNeed($result, $learningNeedUrl, $dateUntil, $dateFrom);
                }
            }
        } else {
            // Do not throw an error, because we want to return an empty array in this case
            $result['message'] = 'Warning, '.$studentId.' is not an existing eav/edu/participant!';
        }

        return $result;
    }

    /**
     * This function gets a learningNeed and if the dateUntil or dateFrom is given also checks if it was created after, before or between these dates.
     * The learningNeed will be added to the given result array that should contain a ['learningNeeds'] array (can be empty []).
     *
     * @param array         $result          the result array containing a ['learningNeeds'], to which a new learningNeed can be added.
     * @param string        $learningNeedUrl the url of a learningNeed to get.
     * @param DateTime|null $dateUntil       a date until DateTime object.
     * @param DateTime|null $dateFrom        a date from DateTime object.
     *
     * @throws Exception
     *
     * @return array the result array containing learningNeeds with the newly gotten learningNeed or an errorMessage. Unless it was created outside the given dates.
     */
    public function getStudentLearningNeed(array $result, string $learningNeedUrl, ?DateTime $dateUntil, ?DateTime $dateFrom): array
    {
        $learningNeed = $this->getLearningNeed(null, $learningNeedUrl);
        if (isset($learningNeed['learningNeed'])) {
            if (isset($dateFrom) || isset($dateUntil)) {
                $dateCreated = new DateTime($learningNeed['learningNeed']['dateCreated']);
                $dateCreated->format('Y-m-d H:i:s');
                if ((isset($dateFrom) && isset($dateUntil) && $dateCreated > $dateFrom && $dateCreated < $dateUntil)
                    || (isset($dateFrom) && !isset($dateUntil) && $dateCreated > $dateFrom)
                    || (isset($dateUntil) && !isset($dateFrom) && $dateCreated < $dateUntil)) {
                    $result['learningNeeds'][] = $learningNeed['learningNeed'];
                }
            } else {
                $result['learningNeeds'][] = $learningNeed['learningNeed'];
            }
        } else {
            $result['learningNeeds'][] = ['errorMessage' => $learningNeed['errorMessage']];
        }

        return $result;
    }

    /**
     * This function checks if the given learningNeed body, studentUrl and if given the learningNeedId are valid to use to create or update a LearningNeed.
     * It also cleans up some values in the learningNeed body that we might not want in there when saving the learningNeed.
     *
     * @param array       $learningNeed   the body of a learningNeed.
     * @param string      $studentUrl     the student url (edu/participant).
     * @param string|null $learningNeedId the id of an already existing learningNeed, for updating it.
     *
     * @throws Exception
     *
     * @return array the result array containing the learningNeed or an errorMessage.
     */
    public function checkLearningNeedValues(array $learningNeed, string $studentUrl, string $learningNeedId = null): array
    {
        $result = [];
        if ($learningNeed['topicOther'] == 'OTHER' && !isset($learningNeed['topicOther'])) {
            $result['errorMessage'] = 'Invalid request, desiredOutComesTopicOther is not set!';
        } elseif ($learningNeed['application'] == 'OTHER' && !isset($learningNeed['applicationOther'])) {
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
        unset($learningNeed['learningNeedId']);
        unset($learningNeed['learningNeedUrl']);
        unset($learningNeed['studentId']);
        unset($learningNeed['participations']);
        $result['learningNeed'] = $learningNeed;

        return $result;
    }

    /**
     * This function sets the participations of a LearningNeed DTO object to return after an api call is done on this DTO.
     * If no participations are present in the learningNeed the participations will be set to an empty array.
     *
     * @param LearningNeed $resource           a LearningNeed DTO object.
     * @param array        $learningNeed       the body of a learningNeed (that might contain participations).
     * @param bool         $skipParticipations if set to true the participations of this LearningNeed DTO will be set to an empty array instead.
     *
     * @throws Exception
     *
     * @return LearningNeed the updated LearningNeed DTO object with participations set.
     */
    public function setResourceParticipations(LearningNeed $resource, array $learningNeed, bool $skipParticipations): LearningNeed
    {
        if (!$skipParticipations && isset($learningNeed['participations'])) {
            foreach ($learningNeed['participations'] as &$participation) {
                $result = $this->participationService->getParticipation(null, $participation);
                if (!isset($result['errorMessage'])) {
                    $participation = $this->participationService->handleResultJson($result['participation'], $learningNeed['id']);
                }
            }
            $resource->setParticipations($learningNeed['participations']);
        } else {
            $resource->setParticipations([]);
        }

        return $resource;
    }

    /**
     * this function creates, sets and returns a LearningNeed DTO object to return after an api call is done on this DTO.
     *
     * @param array       $learningNeed       the body of a learningNeed.
     * @param string|null $studentId          the id of a student (edu/participant) this learningNeed is connected to.
     * @param false       $skipParticipations if set to true the participations of this LearningNeed DTO will be set to an empty array.
     *
     * @throws Exception
     *
     * @return LearningNeed a LearningNeed DTO object with all info from the given learningNeed array set.
     */
    public function handleResult(array $learningNeed, string $studentId = null, bool $skipParticipations = false): LearningNeed
    {
        $resource = new LearningNeed();
        // For some reason setting the id does not work correctly when done inside this function, so do it after calling this handleResult function instead!
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
        $resource = $this->setResourceParticipations($resource, $learningNeed, $skipParticipations);

        if (isset($studentId)) {
            $resource->setStudentId($studentId);
        }
        $resource->setDateCreated($learningNeed['dateCreated']);
        $this->entityManager->persist($resource);

        return $resource;
    }
}
