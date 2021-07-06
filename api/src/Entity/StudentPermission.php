<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentPermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
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
    private UuidInterface $id;

    /**
     * @var Bool A boolean that is true when the permission form was signed.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private bool $didSignPermissionForm;

    /**
     * @var Bool A boolean that is true when the student gives permission to share his/her data with providers.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private bool $hasPermissionToShareDataWithProviders;

    /**
     * @var Bool A boolean that is true when the student gives permission to share his/her data with libraries.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private bool $hasPermissionToShareDataWithLibraries;

    /**
     * @var Bool A boolean that is true when the student gives permission to send information about libraries.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private bool $hasPermissionToSendInformationAboutLibraries;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getDidSignPermissionForm(): bool
    {
        return $this->didSignPermissionForm;
    }

    public function setDidSignPermissionForm(bool $didSignPermissionForm): self
    {
        $this->didSignPermissionForm = $didSignPermissionForm;

        return $this;
    }

    public function getHasPermissionToShareDataWithProviders(): bool
    {
        return $this->hasPermissionToShareDataWithProviders;
    }

    public function setHasPermissionToShareDataWithProviders(bool $hasPermissionToShareDataWithProviders): self
    {
        $this->hasPermissionToShareDataWithProviders = $hasPermissionToShareDataWithProviders;

        return $this;
    }

    public function getHasPermissionToShareDataWithLibraries(): bool
    {
        return $this->hasPermissionToShareDataWithLibraries;
    }

    public function setHasPermissionToShareDataWithLibraries(bool $hasPermissionToShareDataWithLibraries): self
    {
        $this->hasPermissionToShareDataWithLibraries = $hasPermissionToShareDataWithLibraries;

        return $this;
    }

    public function getHasPermissionToSendInformationAboutLibraries(): bool
    {
        return $this->hasPermissionToSendInformationAboutLibraries;
    }

    public function setHasPermissionToSendInformationAboutLibraries(bool $hasPermissionToSendInformationAboutLibraries): self
    {
        $this->hasPermissionToSendInformationAboutLibraries = $hasPermissionToSendInformationAboutLibraries;

        return $this;
    }
}
