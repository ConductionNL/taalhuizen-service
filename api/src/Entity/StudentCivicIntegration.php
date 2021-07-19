<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;
use App\Repository\StudentCivicIntegrationRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity StudentCivicIntegration holds.
 *
 * This DTO is a subresource for the DTO Student. It contains the civic integration details for a Student.
 * The main source that properties of this DTO entity are based on, is the following jira issue: https://lifely.atlassian.net/browse/BISC-76.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "put",
 *          "delete"
 *     },
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=StudentCivicIntegrationRepository::class)
 */
class StudentCivicIntegration
{
    /**
     * @var UuidInterface The UUID identifier of this resource
     *
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private UuidInterface $id;

    /**
     * @var String|null A enum for the status of the civic integration requirement of the student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"YES", "NO", "CURRENTLY_WORKING_ON_INTEGRATION"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"YES", "NO", "CURRENTLY_WORKING_ON_INTEGRATION"},
     *             "example"="YES"
     *         }
     *     }
     * )
     */
    private ?string $civicIntegrationRequirement;

    /**
     * @var String|null The reason why this student has no civic integration requirement.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"FINISHED", "FROM_EU_COUNTRY", "EXEMPTED_OR_ZROUTE"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"FINISHED", "FROM_EU_COUNTRY", "EXEMPTED_OR_ZROUTE"},
     *             "example"="FINISHED"
     *         }
     *     }
     * )
     */
    private ?string $civicIntegrationRequirementReason;

    /**
     * @var DateTimeInterface|null The civic integration requirement finish date for this student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $civicIntegrationRequirementFinishDate;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
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

    public function getCivicIntegrationRequirementFinishDate(): ?DateTimeInterface
    {
        return $this->civicIntegrationRequirementFinishDate;
    }

    public function setCivicIntegrationRequirementFinishDate(?DateTimeInterface $civicIntegrationRequirementFinishDate): self
    {
        $this->civicIntegrationRequirementFinishDate = $civicIntegrationRequirementFinishDate;

        return $this;
    }
}
