<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentCivicIntegrationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=StudentCivicIntegrationRepository::class)
 */
class StudentCivicIntegration
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $civicIntegrationRequirement;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $civicIntegrationRequirementReason;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $civivIntegrationRequirementFinishDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCivicIntegrationRequirement(): ?string
    {
        return $this->civicIntegrationRequirement;
    }

    public function setCivicIntegrationRequirement(?string $civicIntegrationRequirement): self
    {
        $this->civicIntegrationRequirement = $civicIntegrationRequirement;

        return $this;
    }

    public function getCivicIntegrationRequirementReason(): ?string
    {
        return $this->civicIntegrationRequirementReason;
    }

    public function setCivicIntegrationRequirementReason(?string $civicIntegrationRequirementReason): self
    {
        $this->civicIntegrationRequirementReason = $civicIntegrationRequirementReason;

        return $this;
    }

    public function getCivivIntegrationRequirementFinishDate(): ?\DateTimeInterface
    {
        return $this->civivIntegrationRequirementFinishDate;
    }

    public function setCivivIntegrationRequirementFinishDate(?\DateTimeInterface $civivIntegrationRequirementFinishDate): self
    {
        $this->civivIntegrationRequirementFinishDate = $civivIntegrationRequirementFinishDate;

        return $this;
    }
}
