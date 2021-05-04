<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Registration;
use App\Service\CCService;
use App\Service\RegistrationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RegistrationMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;
    private CommonGroundService $commonGroundService;
    private RegistrationService $registrationService;
    private CCService $ccService;

    public function __construct(EntityManagerInterface $entityManager, CommonGroundService $commonGroundService, ParameterBagInterface $parameterBag, RegistrationService $registrationService, CCService $ccService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->parameterBag = $parameterBag;
        $this->registrationService = $registrationService;
        $this->ccService = $ccService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Registration && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createRegistration':
                return $this->createRegistration($context['info']->variableValues['input']);
            case 'removeRegistration':
                return $this->deleteRegistration($context['info']->variableValues['input']);
            case 'acceptRegistration':
                return $this->acceptRegistration($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createRegistration(array $input): Registration
    {
        $this->registrationService->checkRegistrationValues($input);

        //Save student person
        $registrationStudent = $this->inputToStudentPerson($input);
        $registrationStudent = $this->commonGroundService->saveResource($registrationStudent, ['component' => 'cc', 'type' => 'people']);

        //Save registrar person
        $registrationRegistrar = $this->inputToRegistrarPerson($input);
        $registrationRegistrar = $this->commonGroundService->saveResource($registrationRegistrar, ['component' => 'cc', 'type' => 'people']);

        //Save organization
        $organization = $this->inputToOrganization($input, $registrationRegistrar['id']);
        $organization = $this->commonGroundService->saveResource($organization, ['component' => 'cc', 'type' => 'organizations']);

        //Save participant
        $participant['referredBy'] = $organization['@id'];
        $participant['person'] = $registrationStudent['@id'];
        $participant = $this->commonGroundService->saveResource($participant, ['component' => 'edu', 'type' => 'participants']);

        //Save memo
        $memo = $this->inputToMemo($input, $registrationStudent['@id'], $registrationRegistrar['@id']);
        $memo = $this->commonGroundService->saveResource($memo, ['component' => 'memo', 'type' => 'memos']);

        $resourceResult = $this->registrationService->handleResult($registrationStudent, $registrationRegistrar, $organization, $participant, $memo);
        $resourceResult->setId(Uuid::getFactory()->fromString($participant['id']));

        return $resourceResult;
    }

    public function deleteRegistration(array $input): ?Registration
    {
        $result['result'] = [];

        $id = explode('/', $input['id']);
        $id = end($id);
        $result = array_merge($result, $this->registrationService->deleteRegistration($id));

        $result['result'] = False;
        if (isset($result['registration'])){
            $result['result'] = True;
        }

        // If any error was caught throw it
        if (isset($result['errorMessage'])) {
            throw new Exception($result['errorMessage']);
        }

        return null;
    }

    public function acceptRegistration(array $registration): ?Registration
    {
        return null;
    }

    private function inputToMemo(array $input, $studentUrl = null, $registrarUrl = null)
    {
        $memo = [];
        if (isset($input['memo'])) {
            $memo = $this->getMemoProperties($memo, $input['memo'], $studentUrl, $registrarUrl);
        }

        return $memo;
    }

    private function inputToStudentPerson(array $input)
    {
        $student = [];
        //Get student person inputs
        if (isset($input['student'])) {
            $student = $this->getStudentProperties($student, $input['student']);
//            var_dump($student);die();
        }
        return $student;
    }

    private function inputToRegistrarPerson(array $input)
    {
        $registrar = [];
        //Get registrar person inputs
        if (isset($input['registrar'])) {
            $registrar = $this->getRegistrarProperties($registrar, $input['registrar']);
        }

        return $registrar;
    }

    private function getMemoProperties(array $registration, string $memoInput, $studentUrl, $registrarUrl): array
    {
        $registration['author'] = $registrarUrl;
        $student = $this->commonGroundService->getResource($studentUrl);
        $registration['name'] = 'Memo about '.$student['givenName'];
        $registration['topic'] = $studentUrl;
        if (isset($memoInput)) {
            $registration['description'] = $memoInput;
        }

        return $registration;
    }
    private function getStudentProperties(array $registration , array $studentInput): array
    {
        if (isset($studentInput['givenName'])) {
            $registration['givenName'] = $studentInput['givenName'];
        }

        $registration['additionalName'] = $studentInput['additionalName'];

        if (isset($studentInput['familyName'])) {
            $registration['familyName'] = $studentInput['familyName'];
        }
        if (isset($studentInput['email'])) {
            $registration['emails'][0]['name'] = 'Email of '.$registration['givenName'];
            $registration['emails'][0]['email'] = $studentInput['email'];
        }
        if (isset($studentInput['telephone'])) {
            $registration['telephones'][0]['name'] = 'Telephone of '.$registration['givenName'];
            $registration['telephones'][0]['telephone'] = $studentInput['telephone'];
        }
        if (isset($studentInput['address'])) {
            $registration['addresses'][0]['name'] = 'Address of '.$registration['givenName'];
            $registration['addresses'][0] = $studentInput['address'];
        }
        return $registration;
    }

    private function getRegistrarProperties(array $registration, array $registrarInput): array
    {
        if (isset($registrarInput['organizationName'])) {
            $registration['organizationName'] = $registrarInput['organizationName'];
        }
        if (isset($registrarInput['givenName'])) {
            $registration['givenName'] = $registrarInput['givenName'];
        }
        if (isset($registrarInput['additionalName'])) {
            $registration['additionalName'] = $registrarInput['additionalName'];
        }
        if (isset($registrarInput['familyName'])) {
            $registration['familyName'] = $registrarInput['familyName'];
        }
        if (isset($registrarInput['email'])) {
            $registration['emails'][0]['name'] = 'Email of '.$registration['givenName'];
            $registration['emails'][0]['email'] = $registrarInput['email'];
        }
        if (isset($registrarInput['telephone'])) {
            $registration['telephones'][0]['name'] = 'Telephone of '.$registration['givenName'];
            $registration['telephones'][0]['telephone'] = $registrarInput['telephone'];
        }

        return $registration;
    }

    private function inputToOrganization(array $input, string $ccPersonId = null) {
        // Add cc/people to this cc/organization
        if (isset($ccPersonId)) {
            $organization['persons'][] = '/people/'.$ccPersonId;
        } else {
            $organization = [];
        }

        $organization['name'] = $input['registrar']['organizationName'];

        return $organization;
    }

}
