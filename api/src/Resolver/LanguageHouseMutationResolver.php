<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\Address;
use App\Entity\LanguageHouse;
use App\Service\CCService;
use App\Service\WRCService;
use App\Service\EDUService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;


class LanguageHouseMutationResolver implements MutationResolverInterface
{

    private EntityManagerInterface $entityManager;
    private CCService $ccService;
    private WRCService $wrcService;
    private EDUService $eduService;

    public function __construct(EntityManagerInterface $entityManager, CommongroundService $commonGroundService, CCService $ccService, WRCService $wrcService, EDUService $eduService){
        $this->entityManager = $entityManager;
        $this->commonGroundService = $commonGroundService;
        $this->ccService = $ccService;
        $this->wrcService = $wrcService;
        $this->eduService = $eduService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof LanguageHouse && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch($context['info']->operation->name->value){
            case 'createLanguageHouse':
                return $this->createLanguageHouse($context['info']->variableValues['input']);
            case 'updateLanguageHouse':
                return $this->updateLanguageHouse($context['info']->variableValues['input']);
            case 'removeLanguageHouse':
                return $this->deleteLanguageHouse($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    public function createTaalhuis(LanguageHouse $resource): LanguageHouse
    {
        $result['result'] = [];

        $taalhuis = $this->dtoToTaalhuis($resource);

        //create cc organization
        $ccOrganization = $this->ccService->saveOrganization($taalhuis, 'taalhuis', null);
        //create wrc organization
        $wrcOrganization = $this->wrcService->saveOrganization($taalhuis);
        //connect orgs
        $taalhuis =  $this->ccService->saveOrganization($ccOrganization,null,$wrcOrganization['@id']);
        $wrcOrganization = $this->wrcService->saveOrganization($wrcOrganization, $taalhuis['@id']);

        //make program so courses can be added later
        if (!$this->eduService->hasProgram($wrcOrganization)) $this->eduService->saveProgram($wrcOrganization);
        //put together the expected result in $result['result'] for Lifely:
        $result = $this->handleResult($taalhuis);

        return $result;
    }

    public function updateLanguageHouse(array $input): LanguageHouse
    {
        $id = explode('/',$input['id']);
        $languageHouse = new LanguageHouse();
        $languageHouse->setId(Uuid::getFactory()->fromString(end($id)));
        $languageHouse->setEmail($input['email']);
        $languageHouse->setName($input['name']);

        $this->entityManager->persist($languageHouse);
        return $languageHouse;
    }

    public function deleteLanguageHouse(array $languageHouse): ?LanguageHouse
    {
        return null;
    }

    private function dtoToTaalhuis(LanguageHouse $resource)
    {
        if ($resource->getId()){
            $taalhuis['id'] = $resource->getId();
        }
        $taalhuis['name'] = $resource->getName();
        if ($resource->getAddress()){
            $taalhuis['address'] = $resource->getAddress();
        }
        if ($resource->getEmail()) {
            $taalhuis['email'] = $resource->getEmail();
        }
        if ($resource->getPhoneNumber()) {
            $taalhuis['phoneNumber'] = $resource->getPhoneNumber();
        }
        return $taalhuis;
    }

    private function handleResult($taalhuis){
        $resource = new LanguageHouse();
        $resource->setId($taalhuis['id']);
        $resource->setName($taalhuis['name']);
        $resource->setEmail($taalhuis['email']);
        $resource->setPhoneNumber($taalhuis['phoneNumber']);
        //@todo add address
        $this->entityManager->persist($resource);
        return $resource;
    }
}
