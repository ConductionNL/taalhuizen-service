<?php

namespace App\Service;

use App\Entity\Employee;
use App\Entity\LearningNeed;
use App\Entity\Participation;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class ParticipationService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private MrcService $mrcService;

    public function __construct(
        MrcService $mrcService,
        LayerService $layerService
    ) {
        $this->entityManager = $layerService->entityManager;
        $this->commonGroundService = $layerService->commonGroundService;
        $this->eavService = new EAVService($layerService->commonGroundService);
        $this->mrcService = $mrcService;
    }

    /**
     * @param array  $participation   Array with data from the eav/participation
     * @param string $participationId Id of the eav/participation
     *
     * @throws Exception
     *
     * This function handles getting a eav/participation with the given Id.
     *
     * @return array|false A eav/participation is returned from the EAV
     */
    public function handleGettingParticipation(array $participation, string $participationId)
    {
        if (isset($participationId)) {
            // This should be checked with checkParticipationValues, but just in case:
            if (!$this->eavService->hasEavObject(null, 'participations', $participationId)) {
                return $result['errorMessage'] = $result['errorMessage'] = 'Invalid request, '.$participationId.' is not an existing eav/participation!';
            }
            // Update
            $participation = $this->eavService->saveObject($participation, ['entityName' => 'participations', 'eavId' => $participationId]);
        } else {
            // Create
            if (isset($participation['aanbiederName'])) {
                $participation['status'] = 'ACTIVE';
            } else {
                $participation['status'] = 'REFERRED';
            }
            $participation = $this->eavService->saveObject($participation, ['entityName' => 'participations']);
        }

        return $participation;
    }

    /**
     * This function saves a eav/participation with the given participation and Id's.
     *
     * @param array       $participation   Array with data from the eav/participation
     * @param string|null $learningNeedId  Id of the eav/learningNeed
     * @param string|null $participationId Id of the eav/participation
     *
     * @throws Exception
     *
     * @return Participation A eav/participation is returned from the EAV
     */
    public function saveParticipation(array $participation, string $learningNeedId = null, string $participationId = null): Participation
    {
        // Save the participation in EAV
        $participation = $this->handleGettingParticipation($participation, $participationId);

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

        return $result;
    }

    /**
     * This function adds a cc/organizations to a eav/participation with the given aanbiederId and participation.
     *
     * @param string $aanbiederId   Id of the cc/organization
     * @param array  $participation Array with data from the eav/participation
     *
     * @throws Exception
     *
     * @return array A eav/participation is returned from the EAV
     */
    private function addAanbiederToParticipation(string $aanbiederId, array $participation): array
    {
        $result = [];
        $aanbiederUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $aanbiederId]);
        // should already be checked but just in case:
        if (!$this->commonGroundService->isResource($aanbiederUrl)) {
            $result['errorMessage'] = 'Invalid request, aanbiederId is not an existing cc/organization!';
        }

        // Check if aanbieder already has an EAV object
        if ($this->eavService->hasEavObject($aanbiederUrl)) {
            $getOrganization = $this->eavService->getObject(['entityName' => 'organizations', 'componentCode' => 'cc', 'self' => $aanbiederUrl]);
            $organization['participations'] = $getOrganization['participations'];
        }
        if (!isset($organization['participations'])) {
            $organization['participations'] = [];
        }

        // Connect the organization in EAV to the EAV/participation
        if (!in_array($participation['@eav'], $organization['participations'])) {
            array_push($organization['participations'], $participation['@eav']);
            $organization = $this->eavService->saveObject($organization, ['entityName' => 'organizations', 'componentCode' => 'cc', 'self' => $aanbiederUrl]);

            // Add $organization to the $result['organization'] because this is convenient when testing or debugging (mostly for us)
            $result['organization'] = $organization;

            // Update the participation to add the cc/organization to it
            $updateParticipation['aanbieder'] = $organization['@id'];
            $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participation['@eav']]);

            // Add $learningNeed to the $result['learningNeed'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $result;
    }

    /**
     * This function deletes a eav/participation with the given id or url.
     *
     * @param string      $id               Id of the eav/participation
     * @param string|null $url              Url of the eav/participation
     * @param bool|false  $skipLearningNeed If true
     *
     * @throws Exception
     *
     * @return array A eav/participation is returned from the EAV
     */
    public function deleteParticipation(string $id, string $url = null, bool $skipLearningNeed = false): array
    {
        $result = $this->getParticipation($id, $url);

        if (isset($result['participation'])) {
            $participation = $result['participation'];

            // Remove this participation from the EAV/edu/learningNeed
            if (!$skipLearningNeed) {
                $result = $this->removeLearningNeedFromParticipation($participation['learningNeed'], $participation['@eav']);
            }

            // Remove this participation from the EAV/cc/organization
            $result = $this->removeAanbiederFromParticipation($participation['aanbieder'], $participation['@eav']);

            // Delete the participation in EAV
            $this->eavService->deleteObject($participation['eavId']);
            // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $result;
    }

    /**
     * This function deletes a eav/learningNeeds from a eav/participation with the given learningNeedUrl and participationUrl.
     *
     * @param string $learningNeedUrl  Url of the eav/learningNeed
     * @param string $participationUrl Url of the eav/participation
     *
     * @throws Exception
     *
     * @return array A eav/learningNeed is returned from the EAV
     */
    private function removeLearningNeedFromParticipation(string $learningNeedUrl, string $participationUrl): array
    {
        $result = [];
        if ($this->eavService->hasEavObject($learningNeedUrl)) {
            $getLearningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'self' => $learningNeedUrl]);
            if (isset($getLearningNeed['participations'])) {
                $learningNeed['participations'] = array_values(array_filter($getLearningNeed['participations'], function ($learningNeedParticipation) use ($participationUrl) {
                    return $learningNeedParticipation != $participationUrl;
                }));
                $result['learningNeed'] = $this->eavService->saveObject($learningNeed, ['entityName' => 'learning_needs', 'eavId' => $learningNeedUrl]);
            }
        }
        // only works when participation is deleted after, because relation is not removed from the EAV participation object in here
        return $result;
    }

    /**
     * This function deletes a cc/organizations from a eav/participation with the given aanbiederUrl and participationUrl.
     *
     * @param string $aanbiederUrl     Url of the cc/organization
     * @param string $participationUrl Url of the eav/participation
     *
     * @throws Exception
     *
     * @return array A cc/organization is returned from the CC
     */
    private function removeAanbiederFromParticipation(string $aanbiederUrl, string $participationUrl): array
    {
        $result = [];
        if (isset($aanbiederUrl)) {
            if ($this->eavService->hasEavObject($aanbiederUrl)) {
                $getOrganization = $this->eavService->getObject(['entityName' => 'organizations', 'componentCode' => 'cc', 'self' => $aanbiederUrl]);
                if (isset($getOrganization['participations'])) {
                    $organization['participations'] = array_values(array_filter($getOrganization['participations'], function ($organizationParticipation) use ($participationUrl) {
                        return $organizationParticipation != $participationUrl;
                    }));
                    $result['organization'] = $this->eavService->saveObject($organization, ['entityName' => 'organizations', 'componentCode' => 'cc', 'self' => $aanbiederUrl]);
                }
            }
        }
        // only works when participation is deleted after, because relation is not removed from the EAV participation object in here
        return $result;
    }

    /**
     * This function gets a eav/participation with the given id or url.
     *
     * @param string      $id  Id of the eav/participation
     * @param string|null $url Url of the eav/participation
     *
     * @throws Exception
     *
     * @return array A eav/participation is returned from the EAV
     */
    public function getParticipation(string $id, string $url = null): array
    {
        $result = [];
        // Get the participation from EAV and add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            $result['participation'] = $this->getParticipationId($id);
        } elseif (isset($url)) {
            $result['participation'] = $this->getParticipationUrl($url);
        }
        if (isset($result['participation'])) {
            $result = array_merge($result, $this->updateParticipationStatus($result['participation']));
        }

        return $result;
    }

    /**
     * This function gets a eav/participation with the given id.
     *
     * @param string $id Id of the eav/participation
     *
     * @throws Exception
     *
     * @return array A eav/participation is returned from the EAV
     */
    public function getParticipationId(string $id): array
    {
        if ($this->eavService->hasEavObject(null, 'participations', $id)) {
            $participation = $this->eavService->getObject(['entityName' => 'participations', 'eavId' => $id]);
            $result['participation'] = $participation;
        } else {
            throw new Exception('Invalid request, '.$id.' is not an existing eav/participation!');
        }

        return $participation;
    }

    /**
     * This function gets a eav/participation with the given url.
     *
     * @param string $url Url of the eav/participation
     *
     * @throws Exception
     *
     * @return array A eav/participation is returned from the EAV
     */
    public function getParticipationUrl(string $url): array
    {
        if ($this->eavService->hasEavObject($url)) {
            $participation = $this->eavService->getObject(['entityName' => 'participations', 'self' => $url]);
            $result['participation'] = $participation;
        } else {
            throw new Exception('Invalid request, '.$url.' is not an existing eav/participation!');
        }

        return $result['participation'];
    }

    /**
     * This function gets all eav/participations from a eav/learningNeeds with the given learningNeedId.
     *
     * @param string $learningNeedId Id of the eav/learningNeed
     *
     * @throws Exception
     *
     * @return array eav/participations are returned from the EAV
     */
    public function getParticipations(string $learningNeedId): array
    {
        // Get the eav/LearningNeed participations from EAV and add the $participations @id's to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        if ($this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            $result['participations'] = [];
            $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'eavId' => $learningNeedId]);
            if (isset($learningNeed['participations'])) {
                foreach ($learningNeed['participations'] as $participationUrl) {
                    $participation = $this->getParticipation(null, $participationUrl);
                    if (isset($participation['participation'])) {
                        array_push($result['participations'], $participation['participation']);
                    } else {
                        array_push($result['participations'], ['errorMessage' => $participation['errorMessage']]);
                    }
                }
            }
        } else {
            // Do not throw an error, because we want to return an empty array in this case
            $result['message'] = 'Warning, '.$learningNeedId.' is not an existing eav/learning_need!';
        }

        return $result;
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
        } elseif (isset($participation['aanbiederName'])) {
            $updateParticipation['status'] = 'ACTIVE';
        } else {
            $updateParticipation['status'] = 'REFERRED';
        }

        return $updateParticipation;
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
        $result = [];

        // Check what the current status should be
        $updateParticipation = $this->handleParticipationStatus($participation);
        // Check if the status needs to be changed
        if ($participation['status'] != $updateParticipation['status']) {
            // Update status
            $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participation['@eav']]);

            // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $result;
    }

    /**
     * This function adds a mrc/employee to the eav/participations with the given participationId and aanbiederEmployeeId.
     *
     * @param string $participationId     Id of the eav/participation
     * @param string $aanbiederEmployeeId Id of the mrc/employee
     *
     * @throws Exception
     *
     * @return Employee A mrc/employee is returned from MRC
     */
    public function addMentoredParticipationToEmployee(string $participationId, string $aanbiederEmployeeId): Employee
    {
        $result = [];
        $employeeUrl = $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'employees', 'id' => $aanbiederEmployeeId]);
        $result = array_merge($result, $this->getParticipation($participationId));

        array_merge($result, $this->addMentorToParticipation($employeeUrl, $result['participation']));

        return $this->mrcService->getEmployee($aanbiederEmployeeId);
    }

    /**
     * This function adds a mrc/employee to the eav/participations with the given mentorUrl and participation.
     *
     * @param string $mentorUrl     Url of the mrc/employees
     * @param array  $participation Array with data from the eav/participations
     *
     * @throws Exception
     *
     * @return array A mrc/employees and eav/participations are returned from MRC and EAV
     */
    public function addMentorToParticipation(string $mentorUrl, array $participation): array
    {
        $result = [];
        // Make sure this participation has no mentor or group set
        $this->checkMentorGroup($participation);

        // Check if mentor already has an EAV object
        $employee = $this->getEmployeeParticipations($mentorUrl);

        // Save the employee in EAV with the EAV/participant connected to it
        if (!in_array($participation['@eav'], $employee['participations'])) {
            array_push($employee['participations'], $participation['@eav']);
            $employee = $this->eavService->saveObject($employee, ['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $mentorUrl]);

            // Add $employee to the $result['employee'] because this is convenient when testing or debugging (mostly for us)
            $result['employee'] = $employee;

            // Update the participant to add the mrc/employee to it
            $updateParticipation['mentor'] = $employee['@id'];
            $updateParticipation['status'] = 'ACTIVE';
            $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participation['@eav']]);

            // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;

            $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'self' => $participation['learningNeed']]);
            $participant['mentor'] = $mentorUrl;
            $this->commonGroundService->updateResource($participant, $learningNeed['participants'][0]);
        }

        return $result;
    }

    /**
     * This function gets all eav/participations from a mrc/employee with the given mentorUrl.
     *
     * @param string $mentorUrl Url of the mrc/employees
     *
     * @throws Exception
     *
     * @return array The participations of a mrc/employees are returned from MRC
     */
    public function getEmployeeParticipations(string $mentorUrl): array
    {
        // Check if mentor already has an EAV object
        if ($this->eavService->hasEavObject($mentorUrl)) {
            $getEmployee = $this->eavService->getObject(['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $mentorUrl]);
            $employee['participations'] = $getEmployee['participations'];
        }
        if (!isset($employee['participations'])) {
            $employee['participations'] = [];
        }

        return $employee;
    }

    /**
     * This function removes the mrc/employee from the eav/participations with the given mentorUrl and participation.
     *
     * @param string $mentorUrl     Url of the mrc/employees
     * @param array  $participation Array with data from the eav/participations
     *
     * @throws Exception
     *
     * @return Participation A eav/participations is returned from EAV
     */
    public function removeMentorFromParticipation(string $mentorUrl, array $participation): Participation
    {
        $result = [];

        $this->errorRemoveMentorFromParticipation($mentorUrl, $participation);

        $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'self' => $participation['learningNeed']]);
        $participant['mentor'] = '';
        $this->commonGroundService->updateResource($participant, $learningNeed['participants'][0]);

        // Update eav/mrc/employee to remove the participation from it
        $getEmployee = $this->eavService->getObject(['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $mentorUrl]);
        if (isset($getEmployee['participations'])) {
            $employee['participations'] = array_values(array_filter($getEmployee['participations'], function ($employeeParticipation) use ($participation) {
                return $employeeParticipation != $participation['@eav'];
            }));
            $result['employee'] = $this->eavService->saveObject($employee, ['entityName' => 'employees', 'componentCode' => 'mrc', 'self' => $mentorUrl]);
        }
        // Update eav/participation to remove the EAV/mrc/employee from it
        return $this->updateParticipation($participation);
    }

    /**
     * This function checks valid mentorUrl and participation.
     *
     * @param string $mentorUrl     Url of the mrc/employees
     * @param array  $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function errorRemoveMentorFromParticipation(string $mentorUrl, array $participation)
    {
        $this->checkMentorInput($participation);
        $this->checkMentor($mentorUrl, $participation);

        if (!$this->eavService->hasEavObject($mentorUrl)) {
            throw new Exception('Invalid request, '.$mentorUrl.' is not an existing eav/mrc/employee!');
        }
    }

    /**
     * This function checks if there is a mentor with the given participation.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkMentorInput(array $participation)
    {
        if (!isset($participation['mentor'])) {
            throw new Exception('Invalid request, this participation has no mentor!');
        }
    }

    /**
     * This function checks if the given mentorUrl is connected to the given participation.
     *
     * @param string $mentorUrl     Url of the mrc/employees
     * @param array  $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkMentor(string $mentorUrl, array $participation)
    {
        if ($participation['mentor'] != $mentorUrl) {
            throw new Exception('Invalid request, this participation has a different mentor!');
        }
    }

    /**
     * This function adds a group to a eav/participations with the given groupUrl and participation.
     *
     * @param string $groupUrl      Url of the mrc/employees
     * @param array  $participation Array with data from the eav/participations
     *
     * @throws Exception
     *
     * @return Participation A eav/participations is returned from EAV
     */
    public function addGroupToParticipation(string $groupUrl, array $participation): Participation
    {
        $result = [];
        // Make sure this participation has no mentor or group set
        $this->checkMentorGroup($participation);
        // Check if group already has an EAV object
        $group['participations'] = $this->checkEAVGroup($groupUrl);

        // Save the group in EAV with the EAV/participant connected to it
        if (!in_array($participation['@eav'], $group['participations'])) {
            $group['participations'][] = $participation['@eav'];
            $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'self' => $participation['learningNeed']]);
            $participantId = $this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]);
            $group['participants'][] = '/participants/'.$participantId;
            $group = $this->eavService->saveObject($group, ['entityName' => 'groups', 'componentCode' => 'edu', 'self' => $groupUrl]);

            // Add $group to the $result['group'] because this is convenient when testing or debugging (mostly for us)
            $result['group'] = $group;

            // Update the participant to add the edu/group to it
            $updateParticipation['group'] = $group['@id'];
            $updateParticipation['status'] = 'ACTIVE';
            $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participation['@eav']]);

            // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
            $result['participation'] = $participation;
        }

        return $this->handleResult($result['participation']);
    }

    /**
     * This function checks if there is a mentor or group set to the eav/participations with the given participation.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkMentorGroup($participation)
    {
        if (isset($participation['mentor']) || isset($participation['group'])) {
            throw new Exception('Warning, this participation already has a mentor or group set!');
        }
    }

    /**
     * This function checks if there is a eav/groups set to the edu/groups with the given groupUrl.
     *
     * @param string $groupUrl Url of the mrc/employees
     *
     * @throws Exception
     *
     * @return array A eav/participations is returned from EAV
     */
    public function checkEAVGroup(string $groupUrl): array
    {
        if ($this->eavService->hasEavObject($groupUrl)) {
            $getGroup = $this->eavService->getObject(['entityName' => 'groups', 'componentCode' => 'edu', 'self' => $groupUrl]);
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

    /**
     * This function removes a eav/groups from a eav/participations with the given groupUrl and participation.
     *
     * @param string $groupUrl      Url of the mrc/employees
     * @param array  $participation Array with data from the eav/participations
     *
     * @throws Exception
     *
     * @return Participation A eav/participations is returned from EAV
     */
    public function removeGroupFromParticipation(string $groupUrl, array $participation): Participation
    {
        $result = [];
        $this->errorRemoveGroupFromParticipation($groupUrl, $participation);

        // Update eav/edu/group to remove the participation from it
        $getGroup = $this->eavService->getObject(['entityName' => 'groups', 'componentCode' => 'edu', 'self' => $groupUrl]);
        if (isset($getGroup['participations'])) {
            $group['participations'] = array_values(array_filter($getGroup['participations'], function ($groupParticipation) use ($participation) {
                return $groupParticipation != $participation['@eav'];
            }));
            $learningNeed = $this->eavService->getObject(['entityName' => 'learning_needs', 'self' => $participation['learningNeed']]);
            $participantId = $this->commonGroundService->getUuidFromUrl($learningNeed['participants'][0]);
            $group['participants'] = array_values(array_filter($getGroup['participants'], function ($groupParticipant) use ($participantId) {
                return $groupParticipant['id'] != $participantId;
            }));
            if (isset($group['participants'])) {
                foreach ($group['participants'] as &$participant) {
                    $participant = '/participants/'.$participant['id'];
                }
            }
            $result['group'] = $this->eavService->saveObject($group, ['entityName' => 'groups', 'componentCode' => 'edu', 'self' => $groupUrl]);
        }
        // Update eav/participation to remove the EAV/edu/group from it
        return $this->updateParticipation($participation);
    }

    /**
     * This function checks a eav/edu/groups from a eav/participations with the given groupUrl and participation.
     *
     * @param string $groupUrl      Url of the mrc/employees
     * @param array  $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function errorRemoveGroupFromParticipation(string $groupUrl, array $participation)
    {
        $this->checkGroupInput($participation);
        $this->checkGroup($groupUrl, $participation);

        if (!$this->eavService->hasEavObject($groupUrl)) {
            throw new Exception('Invalid request, '.$groupUrl.' is not an existing eav/edu/group!');
        }
    }

    /**
     * This function checks if a eav/participations has a eav/edu/groups with the given participation.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkGroupInput(array $participation)
    {
        if (!isset($participation['group'])) {
            throw new Exception('Invalid request, this participation has no group!');
        }
    }

    /**
     * This function checks if the eav/participations -> group is the same as the given groupUrl.
     *
     * @param string $groupUrl      Url of the mrc/employees
     * @param array  $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkGroup(string $groupUrl, array $participation)
    {
        if ($participation['group'] != $groupUrl) {
            throw new Exception('Invalid request, this participation has a different group!');
        }
    }

    /**
     * This function checks the eav/participations values.
     *
     * @param array       $participation   Array with data from the eav/participations
     * @param string      $aanbiederUrl    Url of the cc/organizations
     * @param string      $learningNeedId  Id of the eav/learningNeeds
     * @param string|null $participationId Id of the eav/participations
     *
     * @throws Exception
     *
     * @return array A eav/participations is returned from EAV
     */
    public function checkParticipationValues(array $participation, string $aanbiederUrl, string $learningNeedId, string $participationId = null): array
    {
        $result = [];
        $this->checkParticipationRequiredFields($participation, $aanbiederUrl, $learningNeedId, $participationId);
        $this->checkParticipationValuesPresenceDates($participation);
        $this->checkParticipationValuesDates($participation);
        // Make sure not to keep these values in the input/participation body when doing and update
        unset($participation['participationId']);
        unset($participation['learningNeedId']);
        $result['participation'] = $participation;

        return $result;
    }

    /**
     * This function checks the eav/participations required fields.
     *
     * @param array       $participation   Array with data from the eav/participations
     * @param string      $aanbiederUrl    Url of the cc/organizations
     * @param string      $learningNeedId  Id of the eav/learningNeeds
     * @param string|null $participationId Id of the eav/participations
     *
     * @throws Exception
     */
    public function checkParticipationRequiredFields(array $participation, string $aanbiederUrl, string $learningNeedId, string $participationId = null)
    {
        $this->checkAanbieder($participation);
        $this->checkTopic($participation);
        $this->checkApplication($participation);
        $this->checkLevel($participation);
        $this->checkAanbiederUrl($aanbiederUrl);
        $this->checkParticipationId($participationId);
        $this->checkLearningNeedId($learningNeedId);
    }

    /**
     * This function checks if the aanbiederId and aanbiederName are both set.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkAanbieder(array $participation)
    {
        if (isset($participation['aanbiederId']) && isset($participation['aanbiederName'])) {
            throw new Exception('Invalid request, aanbiederId and aanbiederName are both set! Please only give one of the two.');
        }
    }

    /**
     * This function checks if the outComesTopicOther is set.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkTopic(array $participation)
    {
        if (isset($participation['topicOther']) && $participation['topicOther'] == 'OTHER' && !isset($participation['topicOther'])) {
            throw new Exception('Invalid request, outComesTopicOther is not set!');
        }
    }

    /**
     * This function checks if the outComesApplicationOther is set.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkApplication(array $participation)
    {
        if (isset($participation['application']) && $participation['application'] == 'OTHER' && !isset($participation['applicationOther'])) {
            throw new Exception('Invalid request, outComesApplicationOther is not set!');
        }
    }

    /**
     * This function checks if the outComesLevelOther is set.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkLevel(array $participation)
    {
        if (isset($participation['level']) && $participation['level'] == 'OTHER' && !isset($participation['levelOther'])) {
            throw new Exception('Invalid request, outComesLevelOther is not set!');
        }
    }

    /**
     * This function checks if the aanbiederUrl is an existing cc/organization.
     *
     * @param string $aanbiederUrl Url of the cc/organizations
     *
     * @throws Exception
     */
    public function checkAanbiederUrl(string $aanbiederUrl)
    {
        if (isset($aanbiederUrl) and !$this->commonGroundService->isResource($aanbiederUrl)) {
            throw new Exception('Invalid request, aanbiederUrl is not an existing cc/organization!');
        }
    }

    /**
     * This function checks if the participationId is an existing eav/participation.
     *
     * @param string $participationId Id of the eav/participations
     *
     * @throws Exception
     */
    public function checkParticipationId(string $participationId)
    {
        if (isset($participationId) and !$this->eavService->hasEavObject(null, 'participations', $participationId)) {
            throw new Exception('Invalid request, participationId is not an existing eav/participation!');
        }
    }

    /**
     * This function checks if the learningNeedId is an existing eav/learning_need.
     *
     * @param string $learningNeedId Id of the eav/learningNeeds
     *
     * @throws Exception
     */
    public function checkLearningNeedId(string $learningNeedId)
    {
        if (isset($learningNeedId) && !$this->eavService->hasEavObject(null, 'learning_needs', $learningNeedId)) {
            throw new Exception('Invalid request, learningNeedId is not an existing eav/learning_need!');
        }
    }

    /**
     * This function checks the dates of the eav/participations.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkParticipationValuesDates(array &$participation)
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
                throw new Exception('Invalid request, detailsEndDate needs to be later than detailsStartDate!');
            }
        }
    }

    /**
     * This function checks the presence dates of the eav/participations.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     */
    public function checkParticipationValuesPresenceDates(array &$participation)
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
                throw new Exception('Invalid request, presenceEndDate needs to be later than presenceStartDate!');
            }
        }
    }

    /**
     * This function handles the eav/participations result.
     *
     * @param array       $participation  Array with data from the eav/participations
     * @param string|null $learningNeedId Id of the eav/learningNeeds
     *
     * @throws Exception
     *
     * @return Participation A eav/participations is returned from EAV
     */
    public function handleResult(array $participation, string $learningNeedId = null): Participation
    {
        // Put together the expected result for Lifely:
        $resource = new Participation();
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

    /**
     * This function handles the eav/participations dates.
     *
     * @param Participation $resource      Object with data from the eav/participations
     * @param array         $participation Array with data from the eav/participations
     *
     * @throws Exception
     *
     * @return array A eav/participations is returned from EAV
     */
    public function handleParticipationDates(Participation $resource, array $participation): array
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

    /**
     * This function handles the eav/participations result in Json.
     *
     * @param array       $participation  Array with data from the eav/participations
     * @param string|null $learningNeedId Id of the eav/learningNeeds
     *
     * @throws Exception
     *
     * @return array A eav/participations resource result is returned in Json
     */
    public function handleResultJson(array $participation, string $learningNeedId = null): array
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

    /**
     * This function handles a update from the eav/participations.
     *
     * @param array $participation Array with data from the eav/participations
     *
     * @throws Exception
     *
     * @return Participation A eav/participations is returned from EAV
     */
    public function updateParticipation(array $participation): Participation
    {
        $updateParticipation['group'] = null;
        $updateParticipation['mentor'] = null;
        $updateParticipation['status'] = 'REFERRED';
        $updateParticipation['presenceEngagements'] = null;
        $updateParticipation['presenceStartDate'] = null;
        $updateParticipation['presenceEndDate'] = null;
        $updateParticipation['presenceEndParticipationReason'] = null;
        $participation = $this->eavService->saveObject($updateParticipation, ['entityName' => 'participations', 'self' => $participation['@eav']]);

        // Add $participation to the $result['participation'] because this is convenient when testing or debugging (mostly for us)
        $result['participation'] = $participation;

        return $this->handleResult($result['participation']);
    }
}
