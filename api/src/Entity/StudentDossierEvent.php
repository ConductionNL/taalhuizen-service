<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
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
 * @ApiResource(
 *     graphql={
 *          "item_query" = {
 *              "item_query" = StudentDossierEventQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = StudentDossierEventQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = StudentDossierEventMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = StudentDossierEventMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = StudentDossierEventMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     }
 * )
 * @ApiFilter(SearchFilter::class, properties={"studentId": "exact"})
 * @ORM\Entity(repositoryClass=DossierRepository::class)
 */
class StudentDossierEvent
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
     * @var string|null The Event of this Student.
     *
     * @Assert\Choice(
     *      {"FINAL_TALK","REMARK","FOLLOW_UP_TALK","INFO_FOR_STORYTELLING","INTAKE"}
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $event;

    /**
     * @var DateTimeInterface date of this student Dossier.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime")
     */
    private DateTimeInterface $eventDate;

    /**
     * @var string description of this student Dossier.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550)
     */
    private string $eventDescription;

    /**
     * @var string|null studentId of this student Dossier.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private ?string $studentId;

    /**
     * @var string|null creator of this student Dossier.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $creatorGivenName;

    /**
     * @var string|null employeeId of this student Dossier.
     *
     * @Groups({"read"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $employeeId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getEventDate(): ?DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(DateTimeInterface $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getEventDescription(): ?string
    {
        return $this->eventDescription;
    }

    public function setEventDescription(string $eventDescription): self
    {
        $this->eventDescription = $eventDescription;

        return $this;
    }

    public function getStudentId(): ?string
    {
        return $this->studentId;
    }

    public function setStudentId(?string $studentId): self
    {
        $this->studentId = $studentId;

        return $this;
    }

    public function getCreatorGivenName(): ?string
    {
        return $this->creatorGivenName;
    }

    public function setCreatorGivenName(?string $creatorGivenName): self
    {
        $this->creatorGivenName = $creatorGivenName;

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
