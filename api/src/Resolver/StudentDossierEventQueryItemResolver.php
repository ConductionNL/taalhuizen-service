<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
use App\Service\EDUService;
use Exception;

class StudentDossierEventQueryItemResolver implements QueryItemResolverInterface
{
    private EDUService $eduService;

    public function __construct(EDUService $eduService)
    {
        $this->eduService = $eduService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (key_exists('studentDossierEventId', $context['info']->variableValues)) {
            $studentDossierEventId = $context['info']->variableValues['studentDossierEventId'];
        } elseif (key_exists('id', $context['args'])) {
            $studentDossierEventId = $context['args']['id'];
        } else {
            throw new Exception('The studentDossierEventId / id was not specified');
        }

        $id = explode('/', $studentDossierEventId);
        if (is_array($id)) {
            $id = end($id);
        }

        return $this->eduService->getEducationEvent($id);
    }
}
