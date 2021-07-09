<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\DossierRepository;
use App\Resolver\StudentDossierEventMutationResolver;
use App\Resolver\StudentDossierEventQueryCollectionResolver;
use App\Resolver\StudentDossierEventQueryItemResolver;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity StudentDossierEvent holds.
 *
 * The main entity associated with this DTO is the edu/EducationEvent: https://taalhuizen-bisc.commonground.nu/api/v1/edu#tag/EducationEvent.
 * DTO StudentDossierEvent exists of a few properties based on this education component entity, that is based on the following schema.org schema: https://schema.org/EducationEvent.
 * But the other main source that properties of this StudentDossierEvent entity are based on, is the following jira epic: https://lifely.atlassian.net/browse/BISC-61.
 * And mainly the following issue: https://lifely.atlassian.net/browse/BISC-83.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=DossierRepository::class)
 */
class StudentDossierEvent
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
     * @var string|null The Event of this Student.
     *
     * @Assert\Choice(
     *      {"FINAL_TALK","REMARK","FOLLOW_UP_TALK","INFO_FOR_STORYTELLING","INTAKE"}
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"FINAL_TALK","REMARK","FOLLOW_UP_TALK","INFO_FOR_STORYTELLING","INTAKE"},
     *             "example"="FINAL_TALK"
     *         }
     *     }
     * )
     */
    private string $event;

    /**
     * @var DateTimeInterface Date of this student Dossier.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime")
     */
    private DateTimeInterface $eventDate;

    /**
     * @var string Description of this student Dossier.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550)
     */
    private string $eventDescription;

    /**
     * @var string|null StudentId of this student Dossier.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $studentId;

    /**
     * @var string|null EmployeeId of this student Dossier.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $employeeId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getEventDate(): DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(DateTimeInterface $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getEventDescription(): string
    {
        return $this->eventDescription;
    }

    public function setEventDescription(string $eventDescription): self
    {
        $this->eventDescription = $eventDescription;

        return $this;
    }

    public function getStudentId(): string
    {
        return $this->studentId;
    }

    public function setStudentId(string $studentId): self
    {
        $this->studentId = $studentId;

        return $this;
    }

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?string $employeeId): self
    {
        $this->employeeId = $employeeId;

        return $this;
    }
}
