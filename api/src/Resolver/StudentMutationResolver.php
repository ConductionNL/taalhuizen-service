<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Student;
use App\Service\CCService;
use App\Service\EDUService;
use App\Service\MrcService;
use App\Service\StudentService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StudentMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private CommonGroundService $commonGroundService;
    private StudentService $studentService;
    private CCService $ccService;
    private EDUService $eduService;
    private MrcService $mrcService;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommongroundService $commonGroundService,
        MrcService $mrcService,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->studentService = new StudentService($entityManager, $commonGroundService);
        $this->ccService = new CCService($entityManager, $commonGroundService);
        $this->eduService = new EDUService($commonGroundService, $entityManager);
        $this->mrcService = $mrcService;
    }

    /**
     * This function determines what function to execute next based on the context.
     *
     * @inheritDoc
     *
     * @param object|null $item    Post object
     * @param array       $context Information about post
     *
     * @throws \Exception
     *
     * @return \App\Entity\Student|object|null Returns a Student object
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

    /**
     * This function creates a Student object with given input.
     *
     * @param array $input Array with students data
     *
     * @throws \Exception
     *
     * @return object Returns a Student object
     */
    public function createStudent(array $input): object
    {
        if (isset($input['languageHouseId'])) {
            $languageHouseId = explode('/', $input['languageHouseId']);
            if (is_array($languageHouseId)) {
                $languageHouseId = end($languageHouseId);
            }
            $input['languageHouseUrl'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        } else {
            throw new \Exception('languageHouseId not given');
        }

        // Do some checks and error handling
        $this->studentService->checkStudentValues($input);

        //todo: only get dto info here in resolver, saving objects should be moved to the studentService->saveStudent, for an example see TestResultService->saveTestResult

        // Transform DTO info to cc/person body
        $person = $this->inputToPerson($input);
        // Save cc/person
        $person = $this->ccService->saveEavPerson($person);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input, $person['@id']);
        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant);

        $employee = $this->inputToEmployee($input, $person['@id']);
        // Save mrc/employee
        $employee = $this->mrcService->createEmployeeArray($employee, true);

        // Then save memos
        $memos = $this->saveMemos($input, $person['@id']);
        if (isset($memos['availabilityMemo']['description'])) {
            $person['availabilityNotes'] = $memos['availabilityMemo']['description'];
        }
        if (isset($memos['motivationMemo']['description'])) {
            $participant['remarks'] = $memos['motivationMemo']['description'];
        }

        // Now put together the expected result in $result['result'] for Lifely:
        $registrar = ['registrarOrganization' => null, 'registrarPerson' => null, 'registrarMemo' => null];
        $resourceResult = $this->studentService->handleResult(['person' => $person, 'participant' => $participant, 'employee' => $employee, 'registrar' => $registrar]);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    /**
     * This function updates a Student with given input.
     *
     * @param array $input Array with students data
     *
     * @throws \Exception
     *
     * @return object Returns a Student object
     */
    public function updateStudent(array $input): object
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
        $person = $this->inputToPerson($input, $student['person']);
        // Save cc/person
        $person = $this->ccService->saveEavPerson($person, $student['person']['@id']);

        // Transform DTO info to edu/participant body
        $participant = $this->inputToParticipant($input);
        // Save edu/participant
        $participant = $this->eduService->saveEavParticipant($participant, $student['participant']['@id']);

        $employee = $this->inputToEmployee($input, $person['@id'], $student['employee']);
        // Save mrc/employee
        $employee = $this->mrcService->updateEmployeeArray($student['employee']['id'], $employee, true, true);

        //Then save memos
        $memos = $this->saveMemos($input, $student['person']['@id']);
        if (isset($memos['availabilityMemo']['description'])) {
            $person['availabilityNotes'] = $memos['availabilityMemo']['description'];
        }
        if (isset($memos['motivationMemo']['description'])) {
            $participant['remarks'] = $memos['motivationMemo']['description'];
        }

        // Now put together the expected result in $result['result'] for Lifely:
        $registrar = ['registrarOrganization' => null, 'registrarPerson' => null, 'registrarMemo' => null];
        $resourceResult = $this->studentService->handleResult(['person' => $person, 'participant' => $participant, 'employee' => $employee, 'registrar' => $registrar]);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        $this->entityManager->persist($resourceResult);

        return $resourceResult;
    }

//    todo:
//    public function removeStudent(array $student): ?Student
//    {
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
//        return null;
//    }

    //todo: should be done in StudentService, for examples how to do this: see StudentService->saveStudent or TestResultService->saveTestResult

    /**
     * This function saves memos with given input.
     *
     * @param array  $input       Array with students data
     * @param string $ccPersonUrl Persons URL as string
     *
     * @return array[] Returns array with memo properties
     */
    private function saveMemos(array $input, string $ccPersonUrl): array
    {
        $availabilityMemo = [];
        $motivationMemo = [];
        $input['languageHouseUrl'] ?? $input['languageHouseUrl'] = null;

        if (isset($input['availabilityDetails'])) {
            if (isset($input['id'])) {
                //todo: also use author as filter, for this: get participant->program->provider (= languageHouseUrl when this memo was created)
                $availabilityMemos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type' => 'memos'], ['name' => 'Availability notes', 'topic' => $ccPersonUrl])['hydra:member'];
                if (count($availabilityMemos) > 0) {
                    $availabilityMemo = $availabilityMemos[0];
                }
            }
            $availabilityMemo = array_merge($availabilityMemo, $this->getMemoFromAvailabilityDetails($input['availabilityDetails'], $ccPersonUrl, $input['languageHouseUrl']));
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
            $motivationMemo = array_merge($motivationMemo, $this->getMemoFromMotivationDetails($input['motivationDetails'], $ccPersonUrl, $input['languageHouseUrl']));
            $motivationMemo = $this->commonGroundService->saveResource($motivationMemo, ['component' => 'memo', 'type' => 'memos']);
        }

        return [
            'availabilityMemo' => $availabilityMemo,
            'motivationMemo'   => $motivationMemo,
        ];
    }

    /**
     * This function retrieves memos from the given availability details.
     *
     * @param array       $availabilityDetails Array with availability details data
     * @param string      $ccPersonUrl         Persons URL as string
     * @param string|null $languageHouseUrl    Language house URL as string
     *
     * @return array Returns a memo as array
     */
    private function getMemoFromAvailabilityDetails(array $availabilityDetails, string $ccPersonUrl, string $languageHouseUrl = null): array
    {
        $memo['name'] = 'Availability notes';
        $memo['description'] = $availabilityDetails['availabilityNotes'];
        $memo['topic'] = $ccPersonUrl;
        if (isset($languageHouseUrl)) {
            $memo['author'] = $languageHouseUrl;
        }

        return $memo;
    }

    /**
     * This function retrieves memos from the given motivation details.
     *
     * @param array       $motivationDetails Array with motivation details data
     * @param string      $ccPersonUrl       Persons URL as string
     * @param string|null $languageHouseUrl  Language house URL as string
     *
     * @return array Returns a memo as array
     */
    private function getMemoFromMotivationDetails(array $motivationDetails, string $ccPersonUrl, string $languageHouseUrl = null): array
    {
        $memo['name'] = 'Remarks';
        $memo['description'] = $motivationDetails['remarks'];
        $memo['topic'] = $ccPersonUrl;
        if (isset($languageHouseUrl)) {
            $memo['author'] = $languageHouseUrl;
        }

        return $memo;
    }

    /**
     * This function passes data from given input to the person array.
     *
     * @param array $input        Array with persons data
     * @param null  $updatePerson Bool if person should be updated
     *
     * @return array Returns person with given data
     */
    private function inputToPerson(array $input, $updatePerson = null): array
    {
        if (isset($input['languageHouseId'])) {
            $person['organization'] = '/organizations/'.$input['languageHouseId'];
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
            $person = $this->getPersonPropertiesFromContactDetails(['person' => $person, 'details' => $input['contactDetails']], $updatePerson);
        }
        if (isset($input['generalDetails'])) {
            $person = $this->getPersonPropertiesFromGeneralDetails(['person' => $person, 'details' => $input['generalDetails']], $updatePerson);
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

    /**
     * This function passes given civic integration details to the person array.
     *
     * @param array $person                  Array with persons data
     * @param array $civicIntegrationDetails Array with civic integration details data
     *
     * @return array Returns a person array with civic integration details
     */
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

    /**
     * This function passes given person details to the person array.
     *
     * @param array $person        Array with persons data
     * @param array $personDetails Array with person details data
     *
     * @return array Returns a person array with civic integration details
     */
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

    /**
     * This function passes given contact details to the person array.
     *
     * @param array $personData
     * @param null $updatePerson Bool if person should be updated
     *
     * @return array Returns a person array with civic integration details
     */
    private function getPersonPropertiesFromContactDetails(array $personData, $updatePerson = null): array
    {
        $personName = $personData['person']['givenName'] ? $personData['person']['familyName'] ? $personData['person']['givenName'].' '.$personData['person']['familyName'] : $personData['person']['givenName'] : '';
        $personData['person'] = $this->getPersonEmailsFromContactDetails($personData['person'], $personData['details'], $personName);
        $personData['person'] = $this->getPersonTelephonesFromContactDetails($personData['person'], $personData['details'], $personName);
        $personData['person'] = $this->getPersonAdressesFromContactDetails($personData['person'], $personData['details'], $personName);
        if (isset($updatePerson)) {
            $person = $this->updatePersonContactDetailsSubobjects($personData['person'], $updatePerson);
        }

        //todo: check in StudentService -> checkStudentValues() if other is chosen for contactPreference, if so make sure an other option is given (see learningNeedservice->checkLearningNeedValues)
        if (isset($contactDetails['contactPreference'])) {
            $person['contactPreference'] = $contactDetails['contactPreference'];
        } elseif ($contactDetails['contactPreferenceOther']) {
            $person['contactPreference'] = $contactDetails['contactPreferenceOther'];
        }

        return $person;
    }

    /**
     * This function passes given emails from contact details to the person array.
     *
     * @param array  $person         Array with persons data
     * @param array  $contactDetails Array with contact details data
     * @param string $personName     Name of person as string
     *
     * @return array Returns a person array with email properties
     */
    private function getPersonEmailsFromContactDetails(array $person, array $contactDetails, string $personName): array
    {
        if (isset($contactDetails['email'])) {
            $person['emails'][0]['name'] = 'Email of '.$personName;
            $person['emails'][0]['email'] = $contactDetails['email'];
        }

        return $person;
    }

    /**
     * This function passes given telephones from contact details to the person array.
     *
     * @param array  $person         Array with persons data
     * @param array  $contactDetails Array with contact details data
     * @param string $personName     Name of person as string
     *
     * @return array Returns a person array with telephone properties
     */
    private function getPersonTelephonesFromContactDetails(array $person, array $contactDetails, string $personName): array
    {
        if (isset($contactDetails['telephone'])) {
            $person['telephones'][0]['name'] = 'Telephone of '.$personName;
            $person['telephones'][0]['telephone'] = $contactDetails['telephone'];
        }
        if (isset($contactDetails['contactPersonTelephone'])) {
            $person['telephones'][1]['name'] = 'Telephone of the contactPerson of '.$personName;
            $person['telephones'][1]['telephone'] = $contactDetails['contactPersonTelephone'];
        }

        return $person;
    }

    /**
     * This function passes given addresses from contact details to the person array.
     *
     * @param array  $person         Array with persons data
     * @param array  $contactDetails Array with contact details data
     * @param string $personName     Name of person as string
     *
     * @return array Returns a person array with address properties
     */
    private function getPersonAdressesFromContactDetails(array $person, array $contactDetails, $personName): array
    {
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

        return $person;
    }

    /**
     * This function updates the person with the contact details's subobjects.
     *
     * @param array $person       Array with persons data
     * @param array $updatePerson Array with data the person needs to be updated with
     *
     * @return array Returns a person array with updated contact details
     */
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

    /**
     * This function updates the person with the contact details's telephones.
     *
     * @param array $person       Array with persons data
     * @param array $updatePerson Array with data the person needs to be updated with
     *
     * @return array Returns a person array with updated contact details telephones
     */
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

    /**
     * This function updates the person with the given general details.
     *
     * @param array $personData
     * @param null $updatePerson Bool if person should be updated or not
     *
     * @return array Returns a person with properties from general details
     */
    private function getPersonPropertiesFromGeneralDetails(array $personData, $updatePerson = null): array
    {
//        $generalDetails = $personData['details'];
        if (isset($personData['details']['countryOfOrigin'])) {
            $personData['person'] = $this->setPersonBirthplaceFromCountryOfOrigin($personData['person'], $personData['details']['countryOfOrigin'], $updatePerson);
        }
        //todo check in StudentService -> checkStudentValues() if this is a iso country code (NL)
        if (isset($personData['details']['nativeLanguage'])) {
            $personData['person']['primaryLanguage'] = $personData['details']['nativeLanguage'];
        }
        if (isset($personData['details']['otherLanguages'])) {
            $personData['person']['speakingLanguages'] = explode(',', $personData['details']['otherLanguages']);
        }
        //todo: check in StudentService -> checkStudentValues() if this is one of the enum values ("MARRIED_PARTNER","SINGLE","DIVORCED","WIDOW")
        if (isset($personData['details']['familyComposition'])) {
            $personData['person']['maritalStatus'] = $personData['details']['familyComposition'];
        }

        // Create the children of this person
        return $this->setPersonChildrenFromGeneralDetails(['person' => $personData['person'], 'details' => $personData['details']], $updatePerson);
    }

    /**
     * This function sets the persons birthplace from the given country of origins.
     *
     * @param array  $person          Array with persons data
     * @param string $countryOfOrigin String that holds country of origin
     * @param null   $updatePerson    Bool if person should be updated
     *
     * @return array Returns person array with birthplace property
     */
    private function setPersonBirthplaceFromCountryOfOrigin(array $person, string $countryOfOrigin, $updatePerson = null): array
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

    /**
     * This function sets the persons children properties from the given general details.
     *
     * @param array $personData
     * @param null $updatePerson Bool if person should be updated
     *
     * @return array Returns person array with children properties
     */
    private function setPersonChildrenFromGeneralDetails(array $personData, $updatePerson = null): array
    {
        $person = $personData['person'];
        $generalDetails = $personData['generalDetails'];
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

    /**
     * This function sets the children dates of birth from given general details.
     *
     * @param string $childrenDatesOfBirth String that holds dates of birth of children.
     *
     * @return array
     */
    private function setChildrenDatesOfBirthFromGeneralDetails(string $childrenDatesOfBirth): array
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

    /**
     * This function sets children from children count.
     *
     * @param array $person               Array with persons data
     * @param int   $childrenCount        Int that counts children
     * @param array $childrenDatesOfBirth Array with childrens date of births
     *
     * @return array Returns an array with childrens data
     */
    private function setChildrenFromChildrenCount(array $person, int $childrenCount, array $childrenDatesOfBirth): array
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

    /**
     * This function updates the persons children contact list with given person.
     *
     * @param array $person       Array with persons data
     * @param array $updatePerson Array with updated persons data
     *
     * @return array Returns an updated person array
     */
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

    /**
     * This function sets the person background details with the given background details.
     *
     * @param array $person            Array with persons data
     * @param array $backgroundDetails Array with background details
     *
     * @return array Returns a person array with background details
     */
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

    /**
     * This function sets the persons DutchN NT details  with the given Dutch NT details.
     *
     * @param array $person         Array with persons data
     * @param array $dutchNTDetails Array with Dutch NT Details
     *
     * @return array Returns person array
     */
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

    /**
     * This function sets the persons availability  with the given availability details.
     *
     * @param array $person              Array with persons data
     * @param array $availabilityDetails Array with availability details
     *
     * @return array Returns person array
     */
    private function getPersonPropertiesFromAvailabilityDetails(array $person, array $availabilityDetails)
    {
        if (isset($availabilityDetails['availability'])) {
            $person['availability'] = $availabilityDetails['availability'];
        }

        return $person;
    }

    /**
     * This function sets the persons availability  with the given availability details.
     *
     * @param array $person            Array with persons data
     * @param array $permissionDetails Array with permission details
     *
     * @return array Returns person array
     */
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

    /**
     * This function passes the given input to an participant.
     *
     * @param array       $input       Array of given data
     * @param string|null $ccPersonUrl String that holds the person URL
     *
     * @throws \Exception
     *
     * @return array Returns an participant array
     */
    private function inputToParticipant(array $input, string $ccPersonUrl = null): array
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

        if (isset($input['languageHouseUrl'])) {
            $programs = $this->commonGroundService->getResourceList(['component' => 'edu', 'type' => 'programs'], ['provider' => $input['languageHouseUrl']])['hydra:member'];
            if (count($programs) > 0) {
                $participant['program'] = '/programs/'.$programs[0]['id'];
            } else {
                throw new Exception('Invalid request, '.$input['languageHouseUrl'].' does not have an existing program (edu/program)!');
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

    /**
     * This function sets the participation referrer details.
     *
     * @param array $participant     Array with participant data
     * @param array $referrerDetails Array with referrer details data
     *
     * @return array Returns participant array
     */
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

    /**
     * This function sets the participant motivation details.
     *
     * @param array $participant       Array with participant details
     * @param array $motivationDetails Array with motivation details
     *
     * @return array Returns participant array
     */
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

    /**
     * This function passes input to an employee array.
     *
     * @param array  $input          Array with employee data
     * @param string $personUrl      String that holds persons URL
     * @param null   $updateEmployee Bool if employee needs to be updated if not
     *
     * @throws \Exception
     *
     * @return array Returns employee array
     */
    private function inputToEmployee(array $input, $personUrl, $updateEmployee = null): array
    {
        $employee = ['person' => $personUrl];
        if (isset($input['contactDetails']['email'])) {
            // set email for creating a user in mrcService
            $employee['email'] = $input['contactDetails']['email'];
        }
        $educations = $this->studentService->getEducationsFromEmployee($updateEmployee, true);
        if (isset($input['educationDetails'])) {
            $employee = $this->getEmployeePropertiesFromEducationDetails($employee, ['lastEducation' => $educations['lastEducation'], 'followingEducation' => $educations['followingEducation'], 'details' => $input['educationDetails']]);
        }
        if (isset($input['courseDetails'])) {
            $employee = $this->getEmployeePropertiesFromCourseDetails($employee, ['course' => $educations['course'], 'details' => $input['courseDetails']]);
        }
        if (isset($input['jobDetails'])) {
            $employee = $this->getEmployeePropertiesFromJobDetails($employee, $input['jobDetails']);
        }
        if (isset($input['speakingLevel'])) {
            $employee['speakingLevel'] = $input['speakingLevel'];
        }

        return $employee;
    }

    /**
     * This function set employee properties from given education details.
     *
     * @param array $employee Array with employee data
     * @param array $educationData
     * @return array Returns employee array
     */
    private function getEmployeePropertiesFromEducationDetails(array $employee, array $educationData): array
    {
        $educationDetails = $educationData['details'];
        $lastEducation = $educationData['lastEducation'];
        $followingEducation = $educationData['followingEducation'];
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

    /**
     * This function retrieves the last eduction from education details.
     *
     * @param array $educationDetails Array with education details
     * @param null  $lastEducation    Bool if this is the last education
     *
     * @return array Returns new education array
     */
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

    /**
     * This function retrieves the following education from education details.
     *
     * @param array $educationDetails Array with education details
     * @param array $newEducation     Array with new education
     *
     * @return array Returns a new education array
     */
    private function getFollowingEducationYesFromEducationDetails(array $educationDetails, array $newEducation): array
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

    /**
     * This function retrieves following education from education details.
     *
     * @param array $educationDetails Array with education details
     * @param $newEducation array new education
     *
     * @return array Returns new education array
     */
    private function getFollowingEducationNoFromEducationDetails(array $educationDetails, array $newEducation): array
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

    /**
     * This function retrieves employee properties from course details.
     *
     * @param array $employee Array with employee data
     * @param array $courseData
     * @return array Returns employee array
     */
    private function getEmployeePropertiesFromCourseDetails(array $employee, array $courseData): array
    {
        $courseDetails = $courseData['details'];
        $course = $courseData['course'];
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
                $newEducation = $this->getCourseProvideCertificateFromCourseDetails($newEducation);
            }
            $employee['educations'][] = $newEducation;
        }

        return $employee;
    }

    /**
     * This function retrieves course provider certificate from course details.
     *
     * @param array $newEducation Array with new education data
     *
     * @return array Returns new education array
     */
    private function getCourseProvideCertificateFromCourseDetails(array $newEducation): array
    {
        if (isset($courseDetails['doesCourseProvideCertificate'])) {
            if ($courseDetails['doesCourseProvideCertificate'] == true) {
                $newEducation['providesCertificate'] = true;
            } else {
                $newEducation['providesCertificate'] = false;
            }
        }

        return $newEducation;
    }

    /**
     * This function retrieves the employee properties from job details.
     *
     * @param array $employee   Array with employee data
     * @param array $jobDetails Array with job details
     *
     * @return array Returns employee array
     */
    private function getEmployeePropertiesFromJobDetails(array $employee, array $jobDetails): array
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
