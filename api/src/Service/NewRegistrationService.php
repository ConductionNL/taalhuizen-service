<?php


namespace App\Service;


use App\Entity\Registration;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class NewRegistrationService
{
    private CCService $ccService;
    private EDUService $eduService;
    private CommonGroundService $commonGroundService;
    private EAVService $eavService;
    private EntityManagerInterface $entityManager;

    public function __construct(LayerService $layerService)
    {
        $this->eduService = new EDUService($layerService->commonGroundService, $layerService->entityManager);
        $this->commonGroundService = $layerService->commonGroundService;
        $this->ccService = new CCService($layerService);
        $this->eavService = new EAVService($this->commonGroundService);
        $this->entityManager = $layerService->entityManager;
    }

    public function createRegistration(Registration $registration): Registration
    {
        $registrarArray = $this->ccService->createPerson($registration->getRegistrar());
        $studentArray = $this->ccService->createPerson($registration->getStudent());
        $organization = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $registration->getLanguageHouseId()]);
        if($registration->getMemo()){
            $memo = [
                'name' => "Generated Memo",
                'author' => $registrarArray['@id'],
                'topic' => $studentArray['@id'],
                'description' => $registration->getMemo(),
            ];
            $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'type' => 'memos']);
        }

        $program = $this->eduService->getProgram($organization);

        $registrationArray = $this->eduService->saveEavParticipant([
            'person'    => $studentArray['@id'],
            'program'   => '/programs/'.$program['id'],
            'status'    => strtolower($registration->getStatus()),
            'referredBy'=> $organization['@id'],
            'type'      => 'registration',
            'registrar' => $registrarArray['@id'],
        ]);

        $registration->getRegistrar()->setId(Uuid::fromString($registrarArray['id']));
        $this->entityManager->persist($registration->getRegistrar());
        $registration->getStudent()->setId(Uuid::fromString($studentArray['id']));
        $this->entityManager->persist($registration->getStudent());
        $registration->setId(Uuid::fromString($registrationArray['id']));
        $this->entityManager->persist($registration);

        return $registration;
    }

    public function getRegistration(string $id): Registration
    {
        $participation = $this->eavService->getObject(['componentCode' => 'edu', 'entityName' => 'participants', 'self' => $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id])]);

    }
}
