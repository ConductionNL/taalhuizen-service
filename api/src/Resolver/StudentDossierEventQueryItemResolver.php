<?php


namespace App\Resolver;


use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\EDUService;

class StudentDossierEventQueryItemResolver implements QueryItemResolverInterface
{

    private EDUService $eduService;

    public function __construct(EDUService $eduService){
        $this->eduService = $eduService;
    }
    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        $studentDossierEventId = explode('/',$context['info']->variableValues['studentDossierEventId']);
        if (is_array($studentDossierEventId)) {
            $studentDossierEventId = end($studentDossierEventId);
        }
        return $this->eduService->getEducationEvent($studentDossierEventId);
    }
}
