<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Student;
use App\Service\CCService;
use App\Service\EAVService;
use App\Service\EDUService;
use App\Service\MrcService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;

class StudentMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;
    private CCService $ccService;
    private EDUService $eduService;
    private MrcService $mrcService;
    private EAVService $eavService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommongroundService $commonGroundService,
        StudentService $studentService,
        CCService $ccService,
        EDUService $eduService,
        MrcService $mrcService,
        EAVService $eavService
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->studentService = $studentService;
        $this->ccService = $ccService;
        $this->eduService = $eduService;
        $this->mrcService = $mrcService;
        $this->eavService = $eavService;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception;
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Student && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createStudent':
                return $this->createStudent($context['info']->variableValues['input']);
            case 'updateStudent':
                return $this->updateStudent($context['info']->variableValues['input']);
            case 'removeStudent':
                return $this->removeStudent($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createStudent(array $input): Student
    {
        if (isset($input['languageHouseId'])) {
            $languageHouseId = explode('/', $input['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
            $languageHouseUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        } else {
            throw new \Exception('languageHouseId not given');
        }

        // Do some checks and error handling
        $this->studentService->checkStudentValues($input, $languageHouseUrl);

        //todo: only get dto info here in resolver, saving objects should be moved to the studentService->saveStudent, for an example see TestResultService->saveTestResult

        // Transform DTO info to cc/person body
        $person = $this->inputToPerson($input, $languageHouseId);
        // Save cc/person
        $person = $this->ccService->saveEavPerson($person);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input, $person['@id'], $languageHouseUrl);
        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant);

        $employee = $this->inputToEmployee($input, $person['@id']);
        // Save mrc/employee
        $employee = $this->mrcService->createEmployee($employee, true);

        // Then save memos
        $memos = $this->saveMemos($input, $person['@id'], $languageHouseUrl);
        if (isset($memos['availabilityMemo']['description'])) {
            $person['availabilityNotes'] = $memos['availabilityMemo']['description'];
        }
        if (isset($memos['motivationMemo']['description'])) {
            $participant['remarks'] = $memos['motivationMemo']['description'];
        }

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->studentService->handleResult($person, $participant, $employee);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    public function updateStudent(array $input): Student
    {
        $studentId = explode('/', $input['id']);
        if (is_array($studentId)) {
            $studentId = end($studentId);
        }
        $student = $this->studentService->getStudent($studentId);

        // Do some checks and error handling
        $this->studentService->checkStudentValues($input);

        //todo: only get dto info here in resolver, saving objects should be moved to the studentService->saveStudent, for an example see TestResultService->saveTestResult

        // Transform DTO info to cc/person body
        $person = $this->inputToPerson($input, null, $student['person']);
        // Save cc/person
        $person = $this->ccService->saveEavPerson($person, $student['person']['@id']);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input);
        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant, $student['participant']['@id']);

        $employee = $this->inputToEmployee($input, $person['@id'], $student['employee']);
        // Save mrc/employee
        $employee = $this->mrcService->updateEmployee($student['employee']['id'], $employee, true, true);

        //Then save memos
        $memos = $this->saveMemos($input, $student['person']['@id']);
        if (isset($memos['availabilityMemo']['description'])) {
            $person['availabilityNotes'] = $memos['availabilityMemo']['description'];
        }
        if (isset($memos['motivationMemo']['description'])) {
            $participant['remarks'] = $memos['motivationMemo']['description'];
        }

        // Now put together the expected result in $result['result'] for Lifely:
        $resourceResult = $this->studentService->handleResult($person, $participant, $employee);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        $this->entityManager->persist($resourceResult);

        return $resourceResult;
    }

    //todo:
    public function removeStudent(array $student): ?Student
    {
//        $result['result'] = [];
//
//        // If studentUrl or studentId is set generate the id for it, needed for eav calls later
//        $studentId = null;
//        if (isset($student['studentUrl'])) {
//            $studentId = $this->commonGroundService->getUuidFromUrl($student['studentUrl']);
//        } elseif (isset($student['id'])) {
//            $studentId = explode('/', $student['id']);
//            if (is_array($studentId)) {
//                $studentId = end($studentId);
//            }
//        } else {
//            throw new Exception('No studentUrl or id was specified');
//        }
//
//        $result = array_merge($result, $this->studentService->deleteStudent($studentId));
//
//        $result['result'] = False;
//        if (isset($result['student'])) {
//            $result['result'] = True;
//        }
//
//        // If any error was caught throw it
//        if (isset($result['errorMessage'])) {
//            throw new Exception($result['errorMessage']);
//        }
        return null;
    }

    //todo: should be done in StudentService, for examples how to do this: see StudentService->saveStudent or TestResultService->saveTestResult
    private function saveMemos(array $input, string $ccPersonUrl, string $languageHouseUrl = null)
    {
        $availabilityMemo = [];
        $motivationMemo = [];

        if (isset($input['availabilityDetails'])) {
            if (isset($input['id'])) {
                //todo: also use author as filter, for this: get participant->program->provider (= languageHouseUrl when this memo was created)
                $availabilityMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['name' => 'Availability notes', 'topic' => $ccPersonUrl])['hydra:member'];
                if (count($availabilityMemos) > 0) {
                    $availabilityMemo = $availabilityMemos[0];
                }
            }
            $availabilityMemo = array_merge($availabilityMemo, $this->getMemoFromAvailabilityDetails($input['availabilityDetails'], $ccPersonUrl, $languageHouseUrl));
            $availabilityMemo = $this->commonGroundService->saveResource($availabilityMemo, ['component' => 'memo', 'type' => 'memos']);
        }

        if (isset($input['motivationDetails'])) {
            if (isset($input['id'])) {
                //todo: also use author as filter, for this: get participant->program->provider (= languageHouseUrl when this memo was created)
                $motivationMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['name' => 'Remarks', 'topic' => $ccPersonUrl])['hydra:member'];
                if (count($motivationMemos) > 0) {
                    $motivationMemo = $motivationMemos[0];
                }
            }
            $motivationMemo = array_merge($motivationMemo, $this->getMemoFromMotivationDetails($input['motivationDetails'], $ccPersonUrl, $languageHouseUrl));
            $motivationMemo = $this->commonGroundService->saveResource($motivationMemo, ['component' => 'memo', 'type' => 'memos']);
        }

        return [
            'availabilityMemo' => $availabilityMemo,
            'motivationMemo'   => $motivationMemo,
        ];
    }

    private function getMemoFromAvailabilityDetails(array $availabilityDetails, string $ccPersonUrl, string $languageHouseUrl = null)
    {
        $memo['name'] = 'Availability notes';
        $memo['description'] = $availabilityDetails['availabilityNotes'];
        $memo['topic'] = $ccPersonUrl;
        if (isset($languageHouseUrl)) {
            $memo['author'] = $languageHouseUrl;
        }

        return $memo;
    }

    private function getMemoFromMotivationDetails(array $motivationDetails, string $ccPersonUrl, string $languageHouseUrl = null)
    {
        $memo['name'] = 'Remarks';
        $memo['description'] = $motivationDetails['remarks'];
        $memo['topic'] = $ccPersonUrl;
        if (isset($languageHouseUrl)) {
            $memo['author'] = $languageHouseUrl;
        }

        return $memo;
    }

    private function inputToPerson(array $input, string $languageHouseId = null, $updatePerson = null)
    {
        if (isset($languageHouseId)) {
            $person['organization'] = '/organizations/'.$languageHouseId;
        } else {
            $person = [];
        }
        if (isset($input['civicIntegrationDetails'])) {
            $person = $this->getPersonPropertiesFromCivicIntegrationDetails($person, $input['civicIntegrationDetails']);
        }
        if (isset($input['personDetails'])) {
            $person = $this->getPersonPropertiesFromPersonDetails($person, $input['personDetails']);
        }
        if (isset($input['contactDetails'])) {
            $person = $this->getPersonPropertiesFromContactDetails($person, $input['contactDetails'], $updatePerson);
        }
        if (isset($input['generalDetails'])) {
            $person = $this->getPersonPropertiesFromGeneralDetails($person, $input['generalDetails'], $updatePerson);
        }
        if (isset($input['backgroundDetails'])) {
            $person = $this->getPersonPropertiesFromBackgroundDetails($person, $input['backgroundDetails']);
        }
        if (isset($input['dutchNTDetails'])) {
            $person = $this->getPersonPropertiesFromDutchNTDetails($person, $input['dutchNTDetails']);
        }
        if (isset($input['availabilityDetails'])) {
            $person = $this->getPersonPropertiesFromAvailabilityDetails($person, $input['availabilityDetails']);
        }
        if (isset($input['permissionDetails'])) {
            $person = $this->getPersonPropertiesFromPermissionDetails($person, $input['permissionDetails']);
        }

        return $person;
    }

    private function getPersonPropertiesFromCivicIntegrationDetails(array $person, array $civicIntegrationDetails): array
    {
        if (isset($civicIntegrationDetails['civicIntegrationRequirement'])) {
            $person['civicIntegrationRequirement'] = $civicIntegrationDetails['civicIntegrationRequirement'];
        }
        if (isset($civicIntegrationDetails['civicIntegrationRequirementReason'])) {
            $person['civicIntegrationRequirementReason'] = $civicIntegrationDetails['civicIntegrationRequirementReason'];
        }
        if (isset($civicIntegrationDetails['civicIntegrationRequirementFinishDate'])) {
            $person['civicIntegrationRequirementFinishDate'] = $civicIntegrationDetails['civicIntegrationRequirementFinishDate'];
        }

        return $person;
    }

    private function getPersonPropertiesFromPersonDetails(array $person, array $personDetails): array
    {
        if (isset($personDetails['givenName'])) {
            $person['givenName'] = $personDetails['givenName'];
        }
        if (isset($personDetails['additionalName'])) {
            $person['additionalName'] = $personDetails['additionalName'];
        }
        if (isset($personDetails['familyName'])) {
            $person['familyName'] = $personDetails['familyName'];
        }
        if (isset($personDetails['gender'])) {
            $person['gender'] = $personDetails['gender'];
        }
        if (isset($personDetails['dateOfBirth'])) {
            $person['birthday'] = $personDetails['dateOfBirth'];
        }

        return $person;
    }

    private function getPersonPropertiesFromContactDetails(array $person, array $contactDetails, $updatePerson = null): array
    {
        $personName = $person['givenName'] ? $person['familyName'] ? $person['givenName'].' '.$person['familyName'] : $person['givenName'] : '';
        if (isset($contactDetails['email'])) {
            $person['emails'][0]['name'] = 'Email of '.$personName;
            $person['emails'][0]['email'] = $contactDetails['email'];
        }
        if (isset($contactDetails['telephone'])) {
            $person['telephones'][0]['name'] = 'Telephone of '.$personName;
            $person['telephones'][0]['telephone'] = $contactDetails['telephone'];
        }
        if (isset($contactDetails['contactPersonTelephone'])) {
            $person['telephones'][1]['name'] = 'Telephone of the contactPerson of '.$personName;
            $person['telephones'][1]['telephone'] = $contactDetails['contactPersonTelephone'];
        }
        $person['addresses'][0]['name'] = 'Address of '.$personName;
        if (isset($contactDetails['street'])) {
            $person['addresses'][0]['street'] = $contactDetails['street'];
        }
        if (isset($contactDetails['postalCode'])) {
            $person['addresses'][0]['postalCode'] = $contactDetails['postalCode'];
        }
        if (isset($contactDetails['locality'])) {
            $person['addresses'][0]['locality'] = $contactDetails['locality'];
        }
        if (isset($contactDetails['houseNumber'])) {
            $person['addresses'][0]['houseNumber'] = $contactDetails['houseNumber'];
        }
        if (isset($contactDetails['houseNumberSuffix'])) {
            $person['addresses'][0]['houseNumberSuffix'] = $contactDetails['houseNumberSuffix'];
        }
        if (isset($updatePerson)) {
            $person = $this->updatePersonContactDetailsSubobjects($person, $updatePerson);
        }

        //todo: check in StudentService -> checkStudentValues() if other is chosen for contactPreference, if so make sure an other option is given (see learningNeedservice->checkLearningNeedValues)
        if (isset($contactDetails['contactPreference'])) {
            $person['contactPreference'] = $contactDetails['contactPreference'];
        } elseif ($contactDetails['contactPreferenceOther']) {
            $person['contactPreference'] = $contactDetails['contactPreferenceOther'];
        }

        return $person;
    }

    private function updatePersonContactDetailsSubobjects(array $person, array $updatePerson): array
    {
        if (isset($person['emails'][0]) && isset($updatePerson['emails'][0]['id'])) {
            //merge person emails into updatePerson emails and update the updatePerson email
            $email = array_merge($updatePerson['emails'][0], $person['emails'][0]);
            $this->commonGroundService->updateResource($email, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'emails', 'id' => $updatePerson['emails'][0]['id']]));

            //unset person emails
            unset($person['emails']);
        }
        if (isset($person['telephones']) && isset($updatePerson['telephones'])) {
            $person = $this->updatePersonContactDetailsTelephones($person, $updatePerson);
        }
        if (isset($person['addresses'][0]) && isset($updatePerson['addresses'][0]['id'])) {
            //merge person addresses into updatePerson addresses and update the updatePerson address
            $address = array_merge($updatePerson['addresses'][0], $person['addresses'][0]);
            $this->commonGroundService->updateResource($address, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'addresses', 'id' => $updatePerson['addresses'][0]['id']]));

            //unset person addresses
            unset($person['addresses']);
        }

        return $person;
    }

    private function updatePersonContactDetailsTelephones(array $person, array $updatePerson): array
    {
        if (isset($person['telephones'][0]) && isset($updatePerson['telephones'][0]['id'])) {
            //merge person telephones into updatePerson telephones and update the updatePerson telephone
            $telephone = array_merge($updatePerson['telephones'][0], $person['telephones'][0]);
            $this->commonGroundService->updateResource($telephone, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'telephones', 'id' => $updatePerson['telephones'][0]['id']]));
        }
        if (isset($person['telephones'][1]) && isset($updatePerson['telephones'][1]['id'])) {
            //merge person telephones into updatePerson telephones and update the updatePerson telephone
            $telephone = array_merge($updatePerson['telephones'][1], $person['telephones'][1]);
            $this->commonGroundService->updateResource($telephone, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'telephones', 'id' => $updatePerson['telephones'][1]['id']]));
        }
        //unset person emails
        unset($person['telephones']);

        return $person;
    }

    private function getPersonPropertiesFromGeneralDetails(array $person, array $generalDetails, $updatePerson = null): array
    {
        if (isset($generalDetails['countryOfOrigin'])) {
            $person = $this->setPersonBirthplaceFromCountryOfOrigin($person, $generalDetails['countryOfOrigin'], $updatePerson);
        }
        //todo check in StudentService -> checkStudentValues() if this is a iso country code (NL)
        if (isset($generalDetails['nativeLanguage'])) {
            $person['primaryLanguage'] = $generalDetails['nativeLanguage'];
        }
        if (isset($generalDetails['otherLanguages'])) {
            $person['speakingLanguages'] = explode(',', $generalDetails['otherLanguages']);
        }
        //todo: check in StudentService -> checkStudentValues() if this is one of the enum values ("MARRIED_PARTNER","SINGLE","DIVORCED","WIDOW")
        if (isset($generalDetails['familyComposition'])) {
            $person['maritalStatus'] = $generalDetails['familyComposition'];
        }

        // Create the children of this person
        return $this->setPersonChildrenFromGeneralDetails($person, $generalDetails, $updatePerson);
    }

    private function setPersonBirthplaceFromCountryOfOrigin(array $person, $countryOfOrigin, $updatePerson = null): array
    {
        $person['birthplace'] = [
            'country' => $countryOfOrigin,
        ];
        if (isset($updatePerson['birthplace']['id'])) {
            //merge person birthplace into updatePerson birthplace and update the updatePerson birthplace
            $address = array_merge($updatePerson['birthplace'], $person['birthplace']);
            $this->commonGroundService->updateResource($address, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'addresses', 'id' => $updatePerson['birthplace']['id']]));

            //unset person birthplace
            unset($person['birthplace']);
        }

        return $person;
    }

    private function setPersonChildrenFromGeneralDetails(array $person, array $generalDetails, $updatePerson = null): array
    {
        if (isset($generalDetails['childrenCount'])) {
            $childrenCount = (int) $generalDetails['childrenCount'];
        }
        if (isset($generalDetails['childrenDatesOfBirth'])) {
            $childrenDatesOfBirth = $this->setChildrenDatesOfBirthFromGeneralDetails($generalDetails['childrenDatesOfBirth']);
            if (!isset($childrenCount)) {
                $childrenCount = count($childrenDatesOfBirth);
            }
        }
        if (isset($childrenCount)) {
            $person['ownedContactLists'][0] = $this->setChildrenFromChildrenCount($person, $childrenCount, $childrenDatesOfBirth ?? null);
            if (isset($updatePerson['ownedContactLists'][0]['id'])) {
                $person = $this->updatePersonChildrenContactList($person, $updatePerson);
            }
        }

        return $person;
    }

    private function setChildrenDatesOfBirthFromGeneralDetails($childrenDatesOfBirth): array
    {
        $childrenDatesOfBirth = explode(',', $childrenDatesOfBirth);
        foreach ($childrenDatesOfBirth as $key => $childrenDateOfBirth) {
            try {
                new \DateTime($childrenDateOfBirth);
            } catch (Exception $e) {
                unset($childrenDatesOfBirth[$key]);
            }
        }

        return $childrenDatesOfBirth;
    }

    private function setChildrenFromChildrenCount(array $person, $childrenCount, $childrenDatesOfBirth): array
    {
        $children = [];
        for ($i = 0; $i < $childrenCount; $i++) {
            $child = [
                'givenName' => 'Child '.($i + 1).' of '.$person['givenName'] ?? '',
            ];
            if (isset($childrenDatesOfBirth[$i])) {
                $child['birthday'] = $childrenDatesOfBirth[$i];
            }
            $children[] = $child;
        }

        return [
            'name'        => 'Children',
            'description' => 'The children of '.$person['givenName'] ?? 'this owner',
            'people'      => $children,
        ];
    }

    private function updatePersonChildrenContactList(array $person, array $updatePerson): array
    {
        // todo: when doing an update, make sure to not keep creating new objects, this is now solved by just deleting all children objects and creating new ones^:
        if (isset($updatePerson['ownedContactLists'][0]['people'])) {
            foreach ($updatePerson['ownedContactLists'][0]['people'] as $key => $child) {
                $this->commonGroundService->deleteResource($child, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $child['id']]));
                unset($updatePerson['ownedContactLists'][0]['people'][$key]);
            }
        }
        //merge person birthplace into updatePerson birthplace and update the updatePerson birthplace
        $contactList = array_merge($updatePerson['ownedContactLists'][0], $person['ownedContactLists'][0]);
        $this->commonGroundService->updateResource($contactList, $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'contact_lists', 'id' => $updatePerson['ownedContactLists'][0]['id']]));

        //unset person ownedContactLists
        unset($person['ownedContactLists']);

        return $person;
    }

    private function getPersonPropertiesFromBackgroundDetails(array $person, array $backgroundDetails): array
    {
        //todo: check in StudentService -> checkStudentValues() for enum options and if other is chosen make sure an other option is given (see learningNeedservice->checkLearningNeedValues)
        // (VOLUNTEER_CENTER, LIBRARY_WEBSITE, SOCIAL_MEDIA, NEWSPAPER, VIA_VIA, OTHER)
        if (isset($backgroundDetails['foundVia'])) {
            $person['foundVia'] = $backgroundDetails['foundVia'];
        } elseif (isset($backgroundDetails['foundViaOther'])) {
            $person['foundVia'] = $backgroundDetails['foundViaOther'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBefore'])) {
            $person['wentToTaalhuisBefore'] = (bool) $backgroundDetails['wentToTaalhuisBefore'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBeforeReason'])) {
            $person['wentToTaalhuisBeforeReason'] = $backgroundDetails['wentToTaalhuisBeforeReason'];
        }
        if (isset($backgroundDetails['wentToTaalhuisBeforeYear'])) {
            $person['wentToTaalhuisBeforeYear'] = $backgroundDetails['wentToTaalhuisBeforeYear'];
        }
        //todo: check in StudentService -> checkStudentValues() if all values in this array are one of the enum values
        // (HOUSEHOLD_MEMBERS, NEIGHBORS, FAMILY_MEMBERS, AID_WORKERS, FRIENDS_ACQUAINTANCES, PEOPLE_AT_MOSQUE_CHURCH, ACQUAINTANCES_SPEAKING_OWN_LANGUAGE, ACQUAINTANCES_SPEAKING_DUTCH)
        if (isset($backgroundDetails['network'])) {
            $person['network'] = $backgroundDetails['network'];
        }
        if (isset($backgroundDetails['participationLadder'])) {
            $person['participationLadder'] = (int) $backgroundDetails['participationLadder'];
        }

        return $person;
    }

    private function getPersonPropertiesFromDutchNTDetails(array $person, array $dutchNTDetails): array
    {
        if (isset($dutchNTDetails['dutchNTLevel'])) {
            $person['dutchNTLevel'] = $dutchNTDetails['dutchNTLevel'];
        }
        if (isset($dutchNTDetails['inNetherlandsSinceYear'])) {
            $person['inNetherlandsSinceYear'] = $dutchNTDetails['inNetherlandsSinceYear'];
        }
        if (isset($dutchNTDetails['languageInDailyLife'])) {
            $person['languageInDailyLife'] = $dutchNTDetails['languageInDailyLife'];
        }
        if (isset($dutchNTDetails['knowsLatinAlphabet'])) {
            $person['knowsLatinAlphabet'] = (bool) $dutchNTDetails['knowsLatinAlphabet'];
        }
        if (isset($dutchNTDetails['lastKnownLevel'])) {
            $person['lastKnownLevel'] = $dutchNTDetails['lastKnownLevel'];
        }

        return $person;
    }

    private function getPersonPropertiesFromAvailabilityDetails(array $person, array $availabilityDetails)
    {
        if (isset($availabilityDetails['availability'])) {
            $person['availability'] = $availabilityDetails['availability'];
        }

        return $person;
    }

    private function getPersonPropertiesFromPermissionDetails(array $person, array $permissionDetails): array
    {
        if (isset($permissionDetails['didSignPermissionForm'])) {
            $person['didSignPermissionForm'] = $permissionDetails['didSignPermissionForm'];
        }
        if (isset($permissionDetails['hasPermissionToShareDataWithAanbieders'])) {
            $person['hasPermissionToShareDataWithAanbieders'] = $permissionDetails['hasPermissionToShareDataWithAanbieders'];
        }
        if (isset($permissionDetails['hasPermissionToShareDataWithLibraries'])) {
            $person['hasPermissionToShareDataWithLibraries'] = $permissionDetails['hasPermissionToShareDataWithLibraries'];
        }
        if (isset($permissionDetails['hasPermissionToSendInformationAboutLibraries'])) {
            $person['hasPermissionToSendInformationAboutLibraries'] = $permissionDetails['hasPermissionToSendInformationAboutLibraries'];
        }

        return $person;
    }

    private function inputToParticipant(array $input, string $ccPersonUrl = null, string $languageHouseUrl = null): array
    {
        // Add cc/person to this edu/participant
        if (isset($ccPersonUrl)) {
            $participant['person'] = $ccPersonUrl;
        } else {
            $participant = [];
        }

        $participant['status'] = 'accepted';

        // EAV or Result objects?
        if (isset($input['readingTestResult'])) {
            $participant['readingTestResult'] = $input['readingTestResult'];
        }
        if (isset($input['writingTestResult'])) {
            $participant['writingTestResult'] = $input['writingTestResult'];
        }

        if (isset($languageHouseUrl)) {
            $programs = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'programs'], ['provider' => $languageHouseUrl])['hydra:member'];
            if (count($programs) > 0) {
                $participant['program'] = '/programs/'.$programs[0]['id'];
            } else {
                throw new Exception('Invalid request, '.$languageHouseUrl.' does not have an existing program (edu/program)!');
            }
        }

        if (isset($input['referrerDetails'])) {
            $participant = $this->getParticipantPropertiesFromReferrerDetails($participant, $input['referrerDetails']);
        }
        if (isset($input['motivationDetails'])) {
            $participant = $this->getParticipantPropertiesFromMotivationDetails($participant, $input['motivationDetails']);
        }

        return $participant;
    }

    private function getParticipantPropertiesFromReferrerDetails(array $participant, array $referrerDetails): array
    {
        if (isset($participant['referredBy'])) {
            $referringOrganization = $this->commonGroundService->getResource($participant['referredBy']);
        } else {
            $referringOrganization = [];
        }
        if (isset($referrerDetails['referringOrganization'])) {
            $referringOrganization['name'] = $referrerDetails['referringOrganization'];
        } elseif (isset($referrerDetails['referringOrganizationOther'])) {
            $referringOrganization['name'] = $referrerDetails['referringOrganizationOther'];
        }
        $referringOrganization = $this->commonGroundService->saveResource($referringOrganization, ['component' => 'cc', 'type' => 'organizations']);

        if (isset($referrerDetails['email'])) {
            if (isset($referringOrganization['emails'][0])) {
                $referringOrganization['emails'][0]['email'] = $referrerDetails['email'];
                $email = $this->commonGroundService->saveResource($referringOrganization['emails'][0], $referringOrganization['emails'][0]['@id']);
            } else {
                $email['name'] = 'Email '.$referringOrganization['name'];
                $email['email'] = $referrerDetails['email'];
                $email['organization'] = '/organizations/'.$referringOrganization['id'];
                $email = $this->commonGroundService->saveResource($email, ['component' => 'cc', 'type' => 'emails']);
            }
            $referringOrganization['emails'][0] = '/emails/'.$email['id'];
            $referringOrganization = $this->commonGroundService->saveResource($referringOrganization, ['component' => 'cc', 'type' => 'organizations']);
        }

        $participant['referredBy'] = $referringOrganization['@id'];

        return $participant;
    }

    private function getParticipantPropertiesFromMotivationDetails(array $participant, array $motivationDetails): array
    {
        if (isset($motivationDetails['desiredSkills'])) {
            $participant['desiredSkills'] = $motivationDetails['desiredSkills'];
        }
        if (isset($motivationDetails['desiredSkillsOther'])) {
            $participant['desiredSkillsOther'] = $motivationDetails['desiredSkillsOther'];
        }
        if (isset($motivationDetails['hasTriedThisBefore'])) {
            $participant['hasTriedThisBefore'] = $motivationDetails['hasTriedThisBefore'];
        }
        if (isset($motivationDetails['hasTriedThisBeforeExplanation'])) {
            $participant['hasTriedThisBeforeExplanation'] = $motivationDetails['hasTriedThisBeforeExplanation'];
        }
        if (isset($motivationDetails['whyWantTheseSkills'])) {
            $participant['whyWantTheseSkills'] = $motivationDetails['whyWantTheseSkills'];
        }
        if (isset($motivationDetails['whyWantThisNow'])) {
            $participant['whyWantThisNow'] = $motivationDetails['whyWantThisNow'];
        }
        if (isset($motivationDetails['desiredLearningMethod'])) {
            $participant['desiredLearningMethod'] = $motivationDetails['desiredLearningMethod'];
        }

        return $participant;
    }

    private function inputToEmployee($input, $personUrl, $updateEmployee = null): array
    {
        $employee = [
            'person' => $personUrl,
        ];

        $lastEducation = $followingEducation = $course = null;
        if (isset($updateEmployee['educations'])) {
            foreach ($updateEmployee['educations'] as $education) {
                switch ($education['description']) {
                    case 'lastEducation':
                        if (!isset($lastEducation)) {
                            $lastEducation = $education;
                        }
                        break;
                    case 'followingEducationNo':
                        if (!isset($followingEducation)) {
                            $followingEducation = $education;
                        }
                        break;
                    case 'followingEducationYes':
                        if (!isset($followingEducation)) {
                            $followingEducation = $this->eavService->getObject('education', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]), 'mrc');
                        }
                        break;
                    case 'course':
                        if (!isset($course)) {
                            $course = $this->eavService->getObject('education', $this->commonGroundService->cleanUrl(['component' => 'mrc', 'type' => 'education', 'id' => $education['id']]), 'mrc');
                        }
                        break;
                }
            }
        }

        if (isset($input['educationDetails'])) {
            $employee = $this->getEmployeePropertiesFromEducationDetails($employee, $input['educationDetails'], $lastEducation, $followingEducation);
        }
        if (isset($input['courseDetails'])) {
            $employee = $this->getEmployeePropertiesFromCourseDetails($employee, $input['courseDetails'], $course);
        }
        if (isset($input['jobDetails'])) {
            $employee = $this->getEmployeePropertiesFromJobDetails($employee, $input['jobDetails']);
        }
        if (isset($input['speakingLevel'])) {
            $employee['speakingLevel'] = $input['speakingLevel'];
        }

        return $employee;
    }

    private function getEmployeePropertiesFromEducationDetails(array $employee, array $educationDetails, $lastEducation = null, $followingEducation = null): array
    {
        if (isset($educationDetails['lastFollowedEducation'])) {
            $employee['educations'][] = $this->getLastEducationFromEducationDetails($educationDetails, $lastEducation);
        }

        if (isset($educationDetails['followingEducationRightNow'])) {
            $newEducation = [];
            if (isset($followingEducation['id'])) {
                $newEducation['id'] = $followingEducation['id'];
            }
            if ($educationDetails['followingEducationRightNow'] == 'YES') {
                $newEducation = $this->getFollowingEducationYesFromEducationDetails($educationDetails, $newEducation);
            } else {
                $newEducation = $this->getFollowingEducationNoFromEducationDetails($educationDetails, $newEducation);
            }
            $employee['educations'][] = $newEducation;
        }

        return $employee;
    }

    private function getLastEducationFromEducationDetails(array $educationDetails, $lastEducation = null): array
    {
        $newEducation = [
            'name'                    => $educationDetails['lastFollowedEducation'],
            'description'             => 'lastEducation',
            'iscedEducationLevelCode' => $educationDetails['lastFollowedEducation'],
        ];
        if (isset($lastEducation['id'])) {
            $newEducation['id'] = $lastEducation['id'];
        }
        if (isset($educationDetails['didGraduate'])) {
            if ($educationDetails['didGraduate'] == true) {
                $newEducation['degreeGrantedStatus'] = 'Granted';
            } else {
                $newEducation['degreeGrantedStatus'] = 'notGranted';
            }
        }

        return $newEducation;
    }

    private function getFollowingEducationYesFromEducationDetails(array $educationDetails, $newEducation): array
    {
        $newEducation['description'] = 'followingEducationYes';
        if (isset($educationDetails['followingEducationRightNowYesStartDate'])) {
            $newEducation['startDate'] = $educationDetails['followingEducationRightNowYesStartDate'];
        }
        if (isset($educationDetails['followingEducationRightNowYesEndDate'])) {
            $newEducation['endDate'] = $educationDetails['followingEducationRightNowYesEndDate'];
        }
        if (isset($educationDetails['followingEducationRightNowYesLevel'])) {
            $newEducation['name'] = $educationDetails['followingEducationRightNowYesLevel'];
            $newEducation['iscedEducationLevelCode'] = $educationDetails['followingEducationRightNowYesLevel'];
        }
        if (isset($educationDetails['followingEducationRightNowYesInstitute'])) {
            $newEducation['institution'] = $educationDetails['followingEducationRightNowYesInstitute'];
        }
        if (isset($educationDetails['followingEducationRightNowYesProvidesCertificate'])) {
            if ($educationDetails['followingEducationRightNowYesProvidesCertificate'] == true) {
                $newEducation['providesCertificate'] = true;
            } else {
                $newEducation['providesCertificate'] = false;
            }
        }

        return $newEducation;
    }

    private function getFollowingEducationNoFromEducationDetails(array $educationDetails, $newEducation): array
    {
        $newEducation['description'] = 'followingEducationNo';
        if (isset($educationDetails['followingEducationRightNowNoEndDate'])) {
            $newEducation['endDate'] = $educationDetails['followingEducationRightNowNoEndDate'];
        }
        if (isset($educationDetails['followingEducationRightNowNoLevel'])) {
            $newEducation['name'] = $educationDetails['followingEducationRightNowNoLevel'];
            $newEducation['iscedEducationLevelCode'] = $educationDetails['followingEducationRightNowNoLevel'];
        }
        if (isset($educationDetails['followingEducationRightNowNoGotCertificate'])) {
            if ($educationDetails['followingEducationRightNowNoGotCertificate'] == true) {
                $newEducation['degreeGrantedStatus'] = 'Granted';
            } else {
                $newEducation['degreeGrantedStatus'] = 'notGranted';
            }
        }

        return $newEducation;
    }

    private function getEmployeePropertiesFromCourseDetails(array $employee, array $courseDetails = null, $course = null): array
    {
        if (isset($courseDetails['isFollowingCourseRightNow'])) {
            if (isset($course['id'])) {
                $newEducation['id'] = $course['id'];
            }
            $newEducation['description'] = 'course';
            if ($courseDetails['isFollowingCourseRightNow'] == true) {
                if (isset($courseDetails['courseName'])) {
                    $newEducation['name'] = $courseDetails['courseName'];
                }
                if (isset($courseDetails['courseTeacher'])) {
                    $newEducation['teacherProfessionalism'] = $courseDetails['courseTeacher'];
                }
                if (isset($courseDetails['courseGroup'])) {
                    $newEducation['groupFormation'] = $courseDetails['courseGroup'];
                }
                if (isset($courseDetails['amountOfHours'])) {
                    $newEducation['amountOfHours'] = $courseDetails['amountOfHours'];
                }
                if (isset($courseDetails['doesCourseProvideCertificate'])) {
                    if ($courseDetails['doesCourseProvideCertificate'] == true) {
                        $newEducation['providesCertificate'] = true;
                    } else {
                        $newEducation['providesCertificate'] = false;
                    }
                }
            }
            $employee['educations'][] = $newEducation;
        }

        return $employee;
    }

    private function getEmployeePropertiesFromJobDetails($employee, $jobDetails): array
    {
        //todo make sure these attributes exist in eav! fixtures and online!
        if (isset($jobDetails['trainedForJob'])) {
            $employee['trainedForJob'] = $jobDetails['trainedForJob'];
        }
        if (isset($jobDetails['lastJob'])) {
            $employee['lastJob'] = $jobDetails['lastJob'];
        }
        if (isset($jobDetails['dayTimeActivities'])) {
            $employee['dayTimeActivities'] = $jobDetails['dayTimeActivities'];
        }
        if (isset($jobDetails['dayTimeActivitiesOther'])) {
            $employee['dayTimeActivitiesOther'] = $jobDetails['dayTimeActivitiesOther'];
        }

        return $employee;
    }
}
