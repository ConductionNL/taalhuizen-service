<?php

namespace App\Service;

use App\Entity\Registration;
use App\Exception\BadRequestPathException;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

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

    public function checkRegistrar(Registration $registration): void
    {
        if ($registration->getRegistrar()->getOrganization() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'registrar.organization');
        }
        if (!$registration->getRegistrar()->getGivenName() || !$registration->getRegistrar()->getFamilyName()) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'registrar.'.$registration->getRegistrar()->getGivenName() ? 'familyName' : 'givenName');
        }
        if (!$registration->getRegistrar()->getEmails() || !$registration->getRegistrar()->getEmails()->getEmail()) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'registrar'.($registration->getRegistrar()->getEmails() ? 'emails.email' : '.emails'));
        }
        if (!$registration->getRegistrar()->getTelephones() || !$registration->getRegistrar()->getTelephones()[0]->getTelephone()) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'registrar'.$registration->getRegistrar()->getTelephones() ? 'telephones[0].telephone' : '.telephones');
        }
    }

    public function checkStudent(Registration $registration): void
    {
        if (!$registration->getStudent()->getGivenName() || !$registration->getStudent()->getFamilyName()) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'student.'.$registration->getRegistrar()->getGivenName() ? 'familyName' : 'givenName');
        }
        if (!$registration->getStudent()->getEmails() || !$registration->getStudent()->getEmails()->getEmail()) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'student'.($registration->getRegistrar()->getEmails() ? 'emails.email' : '.emails'));
        }
        if (!$registration->getStudent()->getTelephones() || !$registration->getStudent()->getTelephones()[0]->getTelephone()) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'student'.$registration->getRegistrar()->getTelephones() ? 'telephones[0].telephone' : '.telephones');
        }
    }

    public function checkRegistration(Registration $registration): void
    {
        if ($registration->getPermissionDetails() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'permissionDetails');
        }
        if ($registration->getLanguageHouseId() == null) {
            throw new BadRequestPathException('Some required fields have not been submitted.', 'languageHouseId');
        }
        $this->checkRegistrar($registration);
        $this->checkStudent($registration);
    }

    public function persistRegistration(Registration $registration, array $arrays): Registration
    {
        $this->entityManager->persist($registration->getRegistrar());
        $registration->getRegistrar()->setId(Uuid::fromString($arrays['registrar']['id']));
        $this->entityManager->persist($registration->getRegistrar());
        $this->entityManager->persist($registration->getStudent());
        $registration->getStudent()->setId(Uuid::fromString($arrays['student']['id']));
        $this->entityManager->persist($registration->getStudent());
        $this->entityManager->persist($registration->getPermissionDetails());
        $registration->getPermissionDetails()->setId(Uuid::fromString($arrays['student']['id']));
        $this->entityManager->persist($registration->getPermissionDetails());
        $this->entityManager->persist($registration);
        $registration->setId(Uuid::fromString($arrays['registration']['id']));
        $this->entityManager->persist($registration);

        return $registration;
    }

    public function createMemo(Registration $registration, array $arrays): void
    {
        if ($registration->getMemo()) {
            $memo = [
                'name'        => 'Generated Memo',
                'author'      => $arrays['registrar']['@id'],
                'topic'       => $arrays['student']['@id'],
                'description' => $registration->getMemo(),
            ];
            $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'type' => 'memos']);
        }
    }

    public function createRegistration(Registration $registration): Registration
    {
        $this->checkRegistration($registration);
        $arrays['registrar'] = $registrarArray = $this->ccService->createPerson($registration->getRegistrar());
        $arrays['student'] = $studentArray = $this->ccService->createPerson($registration->getStudent(), $registration->getPermissionDetails()->setId(Uuid::uuid4()));
        $arrays['organization'] = $organization = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'organizations', 'id' => $registration->getLanguageHouseId()]);
        $this->createMemo($registration, $arrays);
        $program = $this->eduService->getProgram($organization);

        $arrays['registration'] = $registrationArray = $this->eduService->saveEavParticipant([
            'person'    => $arrays['student']['@id'],
            'program'   => '/programs/'.$program['id'],
            'status'    => strtolower($registration->getStatus()),
            'referredBy'=> $arrays['organization']['@id'],
            'type'      => 'registration',
            'registrar' => $arrays['registrar']['@id'],
        ]);

        return $this->persistRegistration($registration, $arrays);
    }

    public function updateMemo(Registration $oldRegistration, string $description): array
    {
        $topic = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'peole', 'id' => $oldRegistration->getStudent()->getId()]);
        $memo = $this->getMemo($topic);
        if ($memo) {
            $memo['description'] = $description;
        } else {
            $memo = [
                'name'        => 'Generated Memo',
                'author'      => $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $oldRegistration->getRegistrar()->getId()]),
                'topic'       => $topic,
                'description' => $description,
            ];
        }

        return $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'type' => 'memos']);
    }

    public function updateStatus(array $update, array &$participant): string
    {
        if (key_exists('status', $update) && ($participant['status'] == 'accepted' || $participant['status'] == 'rejected')) {
            throw new BadRequestPathException('Cannot change status of accepted or rejected registrations', 'status', ['status' => $update['status']]);
        } elseif (key_exists('status', $update)) {
            $participant['status'] = strtolower($update['status']);
        }

        return $participant['status'];
    }

    public function updateRegistration(string $id, array $update): Registration
    {
        $participant = [];
        $oldRegistration = $this->getRegistration($id, $participant);
        unset($participant['program']);
        if (key_exists('student', $update)) {
            $studentArray = $this->ccService->updatePerson($oldRegistration->getStudent()->getId(), $update['student']);
            $participant['person'] = $studentArray['@id'];
        }
        if (key_exists('permissionDetails', $update)) {
            $this->ccService->updatePerson($oldRegistration->getStudent()->getId(), $this->ccService->cleanPermissions($update['permissionDetails']));
        }
        if (key_exists('registrar', $update)) {
            $registrarArray = $this->ccService->updatePerson($oldRegistration->getRegistrar()->getId(), $update['registrar']);
            $participant['registrar'] = $registrarArray['@id'];
        }
        $this->updateStatus($update, $participant);
        if (key_exists('languageHouseId', $update)) {
            $participant['referredBy'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $update['languageHouseId']]);
        }
        if (key_exists('memo', $update)) {
            $memo = $this->updateMemo($oldRegistration, $update['memo']);
        }
        $registrationArray = $this->eduService->saveEavParticipant($participant, $participant['@id']);

        return $this->participationToRegistration($registrationArray);
    }

    public function getMemo(string $topic): ?array
    {
        $memos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type'=> 'memos'], ['topic' => $topic])['hydra:member'];
        $newestDate = null;
        $result = null;
        if (count($memos) < 1) {
            return null;
        } else {
            foreach ($memos as $memo) {
                if (!$newestDate || new \DateTime($memo['dateCreated']) > $newestDate) {
                    $result = $memo;
                    $newestDate = new \DateTime($memo['dateCreated']);
                }
            }
        }

        return $result;
    }

    public function participationToRegistration(array $participation): Registration
    {
        $registration = new Registration();
        $registration->setStudent($this->ccService->createPersonObject($studentArray = $this->ccService->getEavPerson($participation['person'])));
        $registration->setRegistrar($this->ccService->createPersonObject($this->ccService->getEavPerson($participation['registrar'])));
        $registration->setPermissionDetails($this->ccService->createStudentPermissionsObject($studentArray));
        $registration->setStatus(ucfirst($participation['status']));
        $languageHouse = explode('/', $participation['referredBy']);
        $registration->setLanguageHouseId(end($languageHouse));
        $memo = $this->getMemo($studentArray['@id']);
        $registration->setMemo($memo ? $memo['description'] : null);
        $this->entityManager->persist($registration);
        $registration->setId(Uuid::fromString($participation['id']));
        $this->entityManager->persist($registration);

        return $registration;
    }

    public function getRegistration(string $id, array &$participation = []): Registration
    {
        $participation = $this->eavService->getObject([
            'componentCode' => 'edu',
            'entityName'    => 'participants',
            'self'          => $this->commonGroundService->cleanUrl(['component' => 'edu', 'type' => 'participants', 'id' => $id]),
        ]);

        return $this->participationToRegistration($participation);
    }

    public function getRegistrations(array $query): Collection
    {
        $registrations = new ArrayCollection();
        $participants = $this->eavService->getObjectList('participants', 'edu', array_merge($query, ['type' => 'registration']));
        foreach ($participants['hydra:member'] as $participant) {
            $registrations->add($this->participationToRegistration($participant));
        }

        $result = [
            '@context'          => '/contexts/Registration',
            '@id'               => '/registrations',
            '@type'             => 'hydra:Collection',
            'hydra:member'      => $registrations,
            'hydra:totalItems'  => $participants['hydra:totalItems'],
        ];
        if (key_exists('hydra:view', $participants)) {
            $result['hydra:view'] = json_decode(str_replace('/participants', '/registrations', json_encode($participants['hydra:view'])), true);
        }

        return new ArrayCollection($result);
    }

    public function deleteMemos(string $topic): void
    {
        $memos = $this->commonGroundService->getResourceList(['component' => 'memo', 'type'=> 'memos'], ['topic' => $topic])['hydra:member'];

        foreach ($memos as $memo) {
            $this->commonGroundService->deleteResource(null, $memo['@id']);
        }
    }

    public function deleteRegistration(string $id): Response
    {
        $participation = [];

        try {
            $registration = $this->getRegistration($id, $participation);
            $this->ccService->deletePerson($registration->getStudent()->getId());
            $this->ccService->deletePerson($registration->getRegistrar()->getId());
        } catch (ClientException $exception) {
            echo $exception->getMessage();
        }

        $this->deleteMemos($participation['person']);

        $this->eavService->deleteResource(null, ['component' => 'edu', 'type' => 'participants', 'id' => $id]);

        return new Response(null, 204);
    }
}
