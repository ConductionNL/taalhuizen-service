<?php

namespace App\Service;

use App\Entity\LearningNeed;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class LearningNeedService
{
    private EntityManagerInterface $entityManager;
    private $commonGroundService;
    private EAVService $eavService;
    private ParticipationService $participationService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, EAVService $eavService, ParticipationService $participationService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
        $this->participationService = $participationService;
    }

    public function saveLearningNeed($learningNeed, $studentUrl = null, $learningNeedId = null)
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now = $now->format('d-m-Y H:i:s');

        // Save the learningNeed in EAV
        if (isset($learningNeedId)) {
            // Update
            $learningNeed['dateModified'] = $now;
            $learningNeed = $this->eavService->saveObject($learningNeed, 'learning_needs', 'eav', null, $learningNeedId);
        } else {
            // Create
            $learningNeed['dateCreated'] = $now;
            $learningNeed['dateModified'] = $now;
            $learningNeed = $this->eavService->saveObject($learningNeed, 'learning_needs');
        }

        // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        $result['learningNeed'] = $learningNeed;

        // Save the participant in EAV with the EAV/learningNeed connected to it
        if (isset($studentUrl)) {
            $result = array_merge($result, $this->addStudentToLearningNeed($studentUrl, $learningNeed));
        }

        return $result;
    }

    public function addStudentToLearningNeed($studentUrl, $learningNeed)
    {
        $result = [];
        // Check if student already has an EAV object
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            $participant['learningNeeds'] = $getParticipant['learningNeeds'];
        }
        if (!isset($participant['learningNeeds'])) {
            $participant['learningNeeds'] = [];
        }

        // Save the participant in EAV with the EAV/learningNeed connected to it
        if (!in_array($learningNeed['@id'], $participant['learningNeeds'])) {
            array_push($participant['learningNeeds'], $learningNeed['@id']);
            $participant = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);

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
                $learningNeed = $this->eavService->saveObject($updateLearningNeed, 'learning_needs', 'eav', $learningNeed['@eav']);

                // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
                $result['learningNeed'] = $learningNeed;
            }
        }

        return $result;
    }

    public function deleteLearningNeed($id)
    {
        if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
            $result['participants'] = [];
            // Get the learningNeed from EAV
            $learningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $id);

            // Remove this learningNeed from all EAV/edu/participants
            if (isset($learningNeed['participants'])) {
                foreach ($learningNeed['participants'] as $studentUrl) {
                    $studentResult = $this->removeLearningNeedFromStudent($learningNeed['@eav'], $studentUrl);
                    if (isset($studentResult['participant'])) {
                        // Add $studentUrl to the $result['participants'] because this is convenient when testing or debugging (mostly for us)
                        array_push($result['participants'], $studentResult['participant']['@id']);
                    }
                }
            }

            if (isset($learningNeed['participations'])) {
                foreach ($learningNeed['participations'] as $participationUrl) {
                    $this->participationService->deleteParticipation(null, $participationUrl, true);
                }
            }

            // Delete the learningNeed in EAV
            $this->eavService->deleteObject($learningNeed['eavId']);
            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['learningNeed'] = $learningNeed;
        } else {
            $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/learning_need!';
        }

        return $result;
    }

    public function removeLearningNeedFromStudent($learningNeedUrl, $studentUrl)
    {
        $result = [];
        if ($this->eavService->hasEavObject($studentUrl)) {
            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            if (isset($getParticipant['learningNeeds'])) {
                $participant['learningNeeds'] = array_values(array_filter($getParticipant['learningNeeds'], function ($participantLearningNeed) use ($learningNeedUrl) {
                    return $participantLearningNeed != $learningNeedUrl;
                }));
                $result['participant'] = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);
            }
        }
        // only works when learningNeed is deleted after, because relation is not removed from the EAV learningNeed object in here
        return $result;
    }

    public function getLearningNeed($id, $url = null)
    {
        $result = [];
        // Get the learningNeed from EAV and add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'learning_needs', $id)) {
                $learningNeed = $this->eavService->getObject('learning_needs', null, 'eav', $id);
                $result['learningNeed'] = $learningNeed;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$id.' is not an existing eav/learning_need!';
            }
        } elseif (isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $learningNeed = $this->eavService->getObject('learning_needs', $url);
                $result['learningNeed'] = $learningNeed;
            } else {
                $result['errorMessage'] = 'Invalid request, '.$url.' is not an existing eav/learning_need!';
            }
        }

        return $result;
    }

    public function getLearningNeeds($studentId, $dateFrom = null, $dateUntil = null)
    {
        // Get the eav/edu/participant learningNeeds from EAV and add the $learningNeeds @id's to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
        if ($this->eavService->hasEavObject(null, 'participants', $studentId, 'edu')) {
            $result['learningNeeds'] = [];
            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $studentId]);
            $participant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            if (isset($participant['learningNeeds'])) {
                if (isset($dateFrom)) {
                    $dateFrom = new \DateTime($dateFrom);
                    $dateFrom->format('Y-m-d H:i:s');
                }
                if (isset($dateUntil)) {
                    $dateUntil = new \DateTime($dateUntil);
                    $dateUntil->format('Y-m-d H:i:s');
                }
                foreach ($participant['learningNeeds'] as $learningNeedUrl) {
                    $learningNeed = $this->getLearningNeed(null, $learningNeedUrl);
                    if (isset($learningNeed['learningNeed'])) {
                        // if dateFrom and/or dateUntill are set filter out the learningNeeds
                        if (isset($dateFrom) || isset($dateUntil)) {
                            $dateCreated = new \DateTime($learningNeed['learningNeed']['dateCreated']);
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
                }
            }
        } else {
            // Do not throw an error, because we want to return an empty array in this case
            $result['message'] = 'Warning, '.$studentId.' is not an existing eav/edu/participant!';
        }

        return $result;
    }

    public function checkLearningNeedValues($learningNeed, $studentUrl, $learningNeedId = null)
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

    public function handleResult($learningNeed, $studentId = null, $skipParticipations = false)
    {
        // Put together the expected result for Lifely:
        $resource = new LearningNeed();
        // For some reason setting the id does not work correctly when done inside this function, so do it after calling this handleResult function instead!
//        $resource->setId(Uuid::getFactory()->fromString($learningNeed['id']));
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

        if (isset($studentId)) {
            $resource->setStudentId($studentId);
        }
        $resource->setDateCreated($learningNeed['dateCreated']);
        $this->entityManager->persist($resource);

        return $resource;
    }
}
