<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentPermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=StudentPermissionRepository::class)
 */
class StudentPermission
{
    /**
     * @var UuidInterface The UUID identifier of this resource
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $didSignPermissionForm;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasPermissionToShareDataWithAanbieders;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasPermissionToShareDataWithLibraries;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasPermissionToSendInformationAboutLibraries;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getDidSignPermissionForm(): ?bool
    {
        return $this->didSignPermissionForm;
    }

    public function setDidSignPermissionForm(bool $didSignPermissionForm): self
    {
        $this->didSignPermissionForm = $didSignPermissionForm;

        return $this;
    }

    public function getHasPermissionToShareDataWithAanbieders(): ?bool
    {
        return $this->hasPermissionToShareDataWithAanbieders;
    }

    public function setHasPermissionToShareDataWithAanbieders(bool $hasPermissionToShareDataWithAanbieders): self
    {
        $this->hasPermissionToShareDataWithAanbieders = $hasPermissionToShareDataWithAanbieders;

        return $this;
    }

    public function getHasPermissionToShareDataWithLibraries(): ?bool
    {
        return $this->hasPermissionToShareDataWithLibraries;
    }

    public function setHasPermissionToShareDataWithLibraries(bool $hasPermissionToShareDataWithLibraries): self
    {
        $this->hasPermissionToShareDataWithLibraries = $hasPermissionToShareDataWithLibraries;

        return $this;
    }

    public function getHasPermissionToSendInformationAboutLibraries(): ?bool
    {
        return $this->hasPermissionToSendInformationAboutLibraries;
    }

    public function setHasPermissionToSendInformationAboutLibraries(bool $hasPermissionToSendInformationAboutLibraries): self
    {
        $this->hasPermissionToSendInformationAboutLibraries = $hasPermissionToSendInformationAboutLibraries;

        return $this;
    }
}
