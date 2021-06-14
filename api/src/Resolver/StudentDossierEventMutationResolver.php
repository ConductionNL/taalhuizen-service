<?php

namespace App\Resolver;

use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\StudentDossierEvent;
use App\Service\EDUService;
use Doctrine\ORM\EntityManagerInterface;

class StudentDossierEventMutationResolver implements MutationResolverInterface
{
    private EntityManagerInterface $entityManager;
    private EDUService $eduService;

    public function __construct(EntityManagerInterface $entityManager, EDUService $eduService)
    {
        $this->entityManager = $entityManager;
        $this->eduService = $eduService;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($item, array $context)
    {
        if (!$item instanceof StudentDossierEvent && !key_exists('input', $context['info']->variableValues)) {
            return null;
        }
        switch ($context['info']->operation->name->value) {
            case 'createStudentDossierEvent':
                return $this->createStudentDossierEvent($context['info']->variableValues['input']);
            case 'updateStudentDossierEvent':
                return $this->updateStudentDossierEvent($context['info']->variableValues['input']);
            case 'removeStudentDossierEvent':
                return $this->deleteStudentDossierEvent($context['info']->variableValues['input']);
            default:
                return $item;
        }
    }

    /**
     * Creates a student dossier event
     * @param array $studentDossierEventArray The data for the student dossier event
     * @return StudentDossierEvent The resulting student dossier event
     */
    public function createStudentDossierEvent(array $studentDossierEventArray): StudentDossierEvent
    {
        return $this->eduService->createEducationEvent($studentDossierEventArray);
    }

    /**
     * Updates a student dossier event
     * @param array $input The data for the student dossier event
     * @return StudentDossierEvent The resulting student dossier event
     */
    public function updateStudentDossierEvent(array $input): StudentDossierEvent
    {
        $idArray = explode('/', $input['id']);
        $id = end($idArray);

        return $this->eduService->updateEducationEvent($id, $input);
    }

    /**
     * Deletes a student dossier event
     * @param array $studentDossierEvent The data to delete the student dossier event
     * @return StudentDossierEvent|null The resulting data
     */
    public function deleteStudentDossierEvent(array $studentDossierEvent): ?StudentDossierEvent
    {
        $idArray = explode('/', $studentDossierEvent['id']);
        $id = end($idArray);
        $this->eduService->deleteEducationEvent($id);

        return null;
    }
}
