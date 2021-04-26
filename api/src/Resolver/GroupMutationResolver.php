<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\Group;
use App\Service\EAVService;
use App\Service\EDUService;
use App\Entity\LanguageHouse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class GroupMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private EAVService $eavService;
    private EDUService $eduService;

    public function __construct(EntityManagerInterface $entityManager, EAVService $eavService, EDUService $eduService){
        $this->entityManager = $entityManager;
        $this->eavService = $eavService;
        $this->eduService = $eduService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof Group && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createGroup':
                return $this->createGroup($context['info']->variableValues['input']);
            case 'updateGroup':
                return $this->updateGroup($context['info']->variableValues['input']);
            case 'removeGroup':
                return $this->deleteGroup($context['info']->variableValues['input']);
            case 'changeTeachersOfTheGroup':
                return $this->changeTeachersOfTheGroup($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createGroup(array $groupArray): Group
    {
        $result['result'] = [];

        $group = $this->dtoToGroup($groupArray);
        $group = new Group();
        $this->entityManager->persist($group);
        return $group;
    }

    public function updateGroup(array $input): Group
    {
        $id = explode('/',$input['id']);
        $group = new Group();


        $this->entityManager->persist($group);
        return $group;
    }

    public function deleteGroup(array $group): ?Group
    {

        return null;
    }

    public function changeTeachersOfTheGroup(array $group): ?Group
    {

        return null;
    }

    public function dtoToGroup(Group $resource){
        if ($resource->getId()){
            $group['id'] = $resource->getId();
        }
        $group['aanbiederId'] = $resource->getAanbiederId();
        $group['name'] = $resource->getName();
        $group['typeCourse'] = $resource->getTypeCourse();
        $group['outComesGoal'] = $resource->getOutComesGoal();
        $group['outComesTopic'] =$resource->getOutComesTopic();
        if ($resource->getOutComesTopicOther()){
            $group['outComesTopicOther'] = $resource->getOutComesTopicOther();
        }
        $group['outComesApplication'] = $resource->getOutComesApplication();
        if ($resource->getOutComesApplicationOther()){
            $group['outComesApplicationOther'] = $resource->getOutComesApplicationOther();
        }
        $group['outComesLevel'] = $resource->getOutComesLevel();
        if ($resource->getOutComesLevelOther()){
            $group['outComesLevelOther'] = $resource->getOutComesLevelOther();
        }
        $group['detailsIsFormal'] = $resource->getDetailsIsFormal();
        $group['detailsTotalClassHours'] = $resource->getDetailsTotalClassHours();
        $group['detailsCertificateWillBeAwarded'] = $resource->getDetailsCertificateWillBeAwarded();
        if ($resource->getDetailsStartDate()) {
            $group['detailsStartDate'] = $resource->getDetailsStartDate();
        }
        if ($resource->getDetailsEndDate()){
            $group['detailsEndDate'] = $resource->getDetailsEndDate();
        }
        if ($resource->getAvailability()){
            $group['availability'] = $resource->getAvailability();
        }
        if ($resource->getAvailabilityNotes()){
            $group['availabilityNotes'] = $resource->getAvailabilityNotes();
        }
        $group['generalLocation'] = $resource->getGeneralLocation();
        if ($resource->getGeneralParticipantsMin()) {
            $group['generalParticipantsMin'] = $resource->getGeneralParticipantsMin();
        }
        if ($resource->getGeneralParticipantsMax()){
            $group['generalParticipantsMax'] = $resource->getGeneralParticipantsMax();
        }
        if ($resource->getGeneralEvaluation()){
            $group['generalEvaluation'] = $resource->getGeneralEvaluation();
        }
        $group['aanbiederEmployeeIds'] = $resource->getAanbiederEmployeeIds();

        return $group;
    }
}
