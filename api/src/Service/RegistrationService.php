<?php

namespace App\Service;


use App\Entity\LanguageHouse;
use App\Entity\Provider;
use App\Entity\Registration;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RegistrationService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private CommonGroundService $commonGroundService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
    }

    public function getLanguageHouse($languageHouseId)
    {
        $result['languageHouse'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations', 'id' => $languageHouseId]);
        return $result;
    }

    public function getLanguageHouses()
    {
        $result['languageHouses'] = $this->commonGroundService->getResourceList(['component' => 'cc', 'type' => 'organizations'], ['type' => 'Taalhuis'])["hydra:member"];
        return $result;
    }

    public function deleteRegistration($id)
    {
        $participant = $this->commonGroundService->getResourceList(['component'=>'edu', 'type' => 'participants', 'id' => $id]);
        $studentPerson = $this->commonGroundService->getResource($participant['person']);
        $registrarOrganization = $this->commonGroundService->getResource($participant['referredBy']);
        $memo = $this->commonGroundService->getResourceList(['component'=>'memo', 'type' => 'memos', 'topic' => $studentPerson['@id'], 'author' => $registrarOrganization['@id']]);
        $registrarPerson = $this->commonGroundService->getResource($registrarOrganization['persons'][0]['@id']);

        $this->commonGroundService->deleteResource($memo);
        $this->commonGroundService->deleteResource($registrarPerson);
        $this->commonGroundService->deleteResource($studentPerson);
        $this->commonGroundService->deleteResource($registrarOrganization);
        $this->commonGroundService->deleteResource($participant);

        $result['registration'] = $participant;
        return $result;
    }

    public function handleResult($registrationStudent, $registrationRegistrar, $organization, $participant, $memo)
    {
        $resource = new Registration();
        //@todo: setLanguageHouseId has to be set to the taalhuis where the student is referred to
        //@todo: remove address setRegistrar
        $resource->setLanguageHouseId($organization['id']);
        $resource->setStudent($registrationStudent);
        $resource->setRegistrar($registrationRegistrar);
        $resource->setMemo($memo['description']);
        $resource->setStudentId($participant['id']);
        $this->entityManager->persist($resource);
        return $resource;
    }

    public function checkRegistrationValues($input)
    {
        // todo: make sure every subresource json array from the input follows the rules (required, enums, etc) from the corresponding entities!
        $student = $input['student'];
        if (empty($student['givenName']) || empty($student['familyName']) || empty($student['telephone']) || empty($student['email'])) {
            throw new Exception('Invalid request, Student: fill in the mandatory input fields');
        }
        $registrar = $input['registrar'];
        if (empty($registrar['givenName']) || empty($registrar['familyName']) || empty($registrar['telephone']) || empty($registrar['email'])) {
            throw new Exception('Invalid request, Registrar: fill in the mandatory input fields');
        }
    }
}
