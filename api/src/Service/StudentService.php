<?php

namespace App\Service;

use App\Entity\Student;
use App\Service\EAVService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class StudentService
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, EAVService $eavService)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->eavService = $eavService;
    }

    public function saveStudentResource(array $resource, string $type, $resourceUrl = null, $id = null) {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now = $now->format('d-m-Y H:i:s');

        // Save the student in EAV
        if (isset($id)) {
            // Update
            $resource['dateModified'] = $now;
            var_dump($resource);die;
            $resource = $this->eavService->saveObject($resource, $type, 'eav', null, $id);
        } else {
            // Create
            $resource['dateCreated'] = $now;
            $resource['dateModified'] = $now;
            var_dump($resource);die;
            $resource = $this->eavService->saveObject($resource, $type);
        }

        return $resource;
    }

//    public function addStudentToStudent($studentUrl, $student) {
//        $result = [];
//        // Check if student already has an EAV object
//        if ($this->eavService->hasEavObject($studentUrl)) {
//            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
//            $participant['student'] = $getParticipant['student'];
//        } else {
//            $participant['student'] = [];
//        }
//
//        // Save the participant in EAV with the EAV/student connected to it
//        if (!in_array($student['@id'], $participant['student'])) {
//            array_push($participant['student'], $student['@id']);
//            $participant = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);
//
//            // Add $participant to the $result['participant'] because this is convenient when testing or debugging (mostly for us)
//            $result['participant'] = $participant;
//
//            // Update the student to add the EAV/edu/participant to it
//            if (isset($student['participants'])) {
//                $updateStudent['participants'] = $student['participants'];
//            } else {
//                $updateStudent['participants'] = [];
//            }
//            if (!in_array($participant['@id'], $updateStudent['participants'])) {
//                array_push($updateStudent['participants'], $participant['@id']);
//                $student = $this->eavService->saveObject($updateStudent, 'students', 'eav', $student['@eav']);
//
//                // Add $student to the $result['student'] because this is convenient when testing or debugging (mostly for us)
//                $result['student'] = $student;
//            }
//        }
//        return $result;
//    }

    public function deleteStudent($id) {
        if ($this->eavService->hasEavObject(null, 'students', $id)) {
            $result['participants'] = [];
            // Get the student from EAV
            $student = $this->eavService->getObject('students', null, 'eav', $id);

            // Remove this student from all EAV/edu/participants
            foreach ($student['participants'] as $studentUrl) {
                $studentResult = $this->removeStudentFromStudent($student['@eav'], $studentUrl);
                if (isset($studentResult['participant'])) {
                    // Add $studentUrl to the $result['participants'] because this is convenient when testing or debugging (mostly for us)
                    array_push($result['participants'], $studentResult['participant']['@id']);
                }
            }

            // Delete the student in EAV
            $this->eavService->deleteObject($student['eavId']);
            // Add $student to the $result['student'] because this is convenient when testing or debugging (mostly for us)
            $result['student'] = $student;
        } else {
            $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/student!';
        }
        return $result;
    }

//    public function removeStudentFromStudent($studentUrl, $studentUrl) {
//        $result = [];
//        if ($this->eavService->hasEavObject($studentUrl)) {
//            $getParticipant = $this->eavService->getObject('participants', $studentUrl, 'edu');
//            $participant['student'] = array_filter($getParticipant['student'], function($participantStudent) use($studentUrl) {
//                return $participantStudent != $studentUrl;
//            });
//            $result['participant'] = $this->eavService->saveObject($participant, 'participants', 'edu', $studentUrl);
//        }
//        return $result;
//    }

    public function getStudent($id, $url = null) {
        $result = [];
        // Get the student from EAV and add $student to the $result['student'] because this is convenient when testing or debugging (mostly for us)
        if (isset($id)) {
            if ($this->eavService->hasEavObject(null, 'students', $id)) {
                $student = $this->eavService->getObject('students', null, 'eav', $id);
                $result['student'] = $student;
            } else {
                $result['errorMessage'] = 'Invalid request, '. $id .' is not an existing eav/student!';
            }
        } elseif(isset($url)) {
            if ($this->eavService->hasEavObject($url)) {
                $student = $this->eavService->getObject('students', $url);
                $result['student'] = $student;
            } else {
                $result['errorMessage'] = 'Invalid request, '. $url .' is not an existing eav/student!';
            }
        }
        return $result;
    }

    public function getStudents($studentId) {
        // Get the eav/edu/participant student from EAV and add the $student @id's to the $result['student'] because this is convenient when testing or debugging (mostly for us)
        if ($this->eavService->hasEavObject(null, 'participants', $studentId, 'edu')) {
            $result['student'] = [];
            $studentUrl = $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $studentId]);
            $participant = $this->eavService->getObject('participants', $studentUrl, 'edu');
            foreach ($participant['student'] as $studentUrl) {
                $student = $this->getStudent(null, $studentUrl);
                if (isset($student['student'])) {
                    array_push($result['student'], $student['student']);
                } else {
                    array_push($result['student'], ['errorMessage' => $student['errorMessage']]);
                }
            }
        } else {
            $result['message'] = 'Warning, '. $studentId .' is not an existing eav/edu/participant!';
        }
        return $result;
    }

    public function checkStudentValues($student, $studentUrl, $studentId = null) {
        $result = [];
//        if ($student['topicOther'] == 'OTHER' && !isset($student['applicationOther'])) {
//            $result['errorMessage'] = 'Invalid request, desiredOutComesTopicOther is not set!';
//        } elseif($student['application'] == 'OTHER' && !isset($student['applicationOther'])) {
//            $result['errorMessage'] = 'Invalid request, desiredOutComesApplicationOther is not set!';
//        } elseif ($student['level'] == 'OTHER' && !isset($student['levelOther'])) {
//            $result['errorMessage'] = 'Invalid request, desiredOutComesLevelOther is not set!';
//        } elseif ($student['offerDifference'] == 'YES_OTHER' && !isset($student['offerDifferenceOther'])) {
//            $result['errorMessage'] = 'Invalid request, offerDifferenceOther is not set!';
//        } elseif (isset($studentUrl) and !$this->commonGroundService->isResource($studentUrl)) {
//            $result['errorMessage'] = 'Invalid request, studentId is not an existing edu/participant!';
//        } elseif (isset($studentId) and !$this->eavService->hasEavObject(null, 'students', $studentId)) {
//            $result['errorMessage'] = 'Invalid request, studentId and/or studentUrl is not an existing eav/student!';
//        }
        // Make sure not to keep these values in the input/student body when doing and update
        unset($student['studentId']); unset($student['studentUrl']);
        unset($student['studentId']); unset($student['participations']);
        $result['student'] = $student;
        return $result;
    }

    public function handleResult($student, $studentId = null) {
        // Put together the expected result for Lifely:
        $resource = new Student();
        // For some reason setting the id does not work correctly when done inside this function, so do it after calling this handleResult function instead!
//        $resource->setId(Uuid::getFactory()->fromString($student['id']));
        if (isset($student['lastName'])) {
            $resource->setLastName($student['lastName']);
        }
        if (isset($student['middleName'])) {
            $resource->setMiddleName($student['middleName']);
        }
        if (isset($student['nickname'])) {
            $resource->setNickname($student['nickname']);
        }
        if (isset($student['gender'])) {
            $resource->setGender($student['gender']);
        }
        if (isset($student['dateOfBirth'])) {
            $resource->setDateOfBirth($student['dateOfBirth']);
        }
        if (isset($student['streetAndHouseNumber'])) {
            $resource->setStreetAndHouseNumber($student['streetAndHouseNumber']);
        }
        if (isset($student['postalCode'])) {
            $resource->setPostalCode($student['postalCode']);
        }
        if (isset($student['place'])) {
            $resource->setPlace($student['place']);
        }
        if (isset($student['phoneNumber'])) {
            $resource->setPhoneNumber($student['phoneNumber']);
        }
        if (isset($student['phoneNumberContactPerson'])) {
            $resource->setPhoneNumberContactPerson($student['phoneNumberContactPerson']);
        }
        if (isset($student['email'])) {
            $resource->setEmail($student['email']);
        }
        if (isset($student['availability'])) {
            $resource->setAvailability($student['availability']);
        }
        // TODO: when participation resolver is done, also make sure to connect and return the participations of this student
        // TODO: add 'verwijzingen' in EAV to connect student to participations¿
//        $resource->setParticipations([]);

        if (isset($studentId)) {
            $resource->setStudentId($studentId);
        }
        $this->entityManager->persist($resource);
        return $resource;
    }
}
