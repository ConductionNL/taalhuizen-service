<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use App\Resolver\GroupMutationResolver;
use App\Resolver\GroupQueryCollectionResolver;
use App\Resolver\GroupQueryItemResolver;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use phpDocumentor\Reflection\Types\Integer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     },
 *     graphql={
 *          "item_query" = {
 *              "item_query" = GroupQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = GroupQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = GroupMutationResolver::class,
 *              "write" = false,
 *
 *          },
 *          "update" = {
 *              "mutation" = GroupMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = GroupMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "active" = {
 *              "collection_query" = GroupQueryCollectionResolver::class
 *          },
 *          "future" = {
 *              "collection_query" = GroupQueryCollectionResolver::class
 *          },
 *          "completed" = {
 *              "collection_query" = GroupQueryCollectionResolver::class
 *          },
 *          "participantsOfThe" = {
 *              "collection_query" = GroupQueryCollectionResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}}
 *          },
 *          "changeTeachersOfThe" = {
 *              "mutation" = GroupMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass=GroupRepository::class)
 * @ApiFilter(SearchFilter::class, properties={"aanbiederId" = "exact"})
 * @ORM\Table(name="`group`")
 */
class Group
{
//   Id of the group, was called in the graphql-schema 'groupId', changed to 'id'
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

//   Organization of the group, was called in the graphql-schema 'aanbiederId', changed to 'organization'(Organization Entity)
    /**
     * @var Organization Organization of this group
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Organization::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private Organization $organization;

    /**
     * @var string Name of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @var string Type course of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $typeCourse;

    /**
     * @var string Detail is formal of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $detailsIsFormal;

    /**
     * @var int Detail is formal of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", length=255)
     */
    private int $detailsTotalClassHours;

    /**
     * @var bool Details certificate will be awarded of this group.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private bool $detailsCertificateWillBeAwarded;

//   start and end date of the group, was called in the graphql-schema 'detailsStartDate' and 'detailsEndDate', changed to 'startDate' and 'endDate' related to schema.org
    /**
     * @var ?DateTime Start date of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $startDate;

    /**
     * @var ?DateTime End date of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $endDate;

    /**
     * @var ?Availability Availability of this group.
     *
     * @ORM\OneToOne(targetEntity=Availability::class, cascade={"persist", "remove"})
     */
    private ?Availability $availability;

    /**
     * @var ?string Availability note of this group.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private ?string $availabilityNotes;

    /**
     * @var string General location of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $generalLocation;

//   min and max participations of the group, was called in the graphql-schema 'generalParticipantsMin' and 'generalParticipantsMax', changed to 'minParticipations' and 'maxParticipations' related to schema.org
    /**
     * @var ?int Min participation's of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private ?int $minParticipations;

    /**
     * @var ?int Max participation's of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private ?int $maxParticipations;

    /**
     * @var ?string General evaluation of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $generalEvaluation;

    /**
     * @var array Employee ids of this group.
     *
     * @Assert\NotNull
     * @ORM\Column(type="array")
     */
    private array $employeeIds = [];

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTypeCourse(): string
    {
        return $this->typeCourse;
    }

    public function setTypeCourse(string $typeCourse): self
    {
        $this->typeCourse = $typeCourse;

        return $this;
    }

    public function getDetailsIsFormal(): string
    {
        return $this->detailsIsFormal;
    }

    public function setDetailsIsFormal(string $detailsIsFormal): self
    {
        $this->detailsIsFormal = $detailsIsFormal;

        return $this;
    }

    public function getDetailsTotalClassHours(): int
    {
        return $this->detailsTotalClassHours;
    }

    public function setDetailsTotalClassHours(int $detailsTotalClassHours): self
    {
        $this->detailsTotalClassHours = $detailsTotalClassHours;

        return $this;
    }

    public function getDetailsCertificateWillBeAwarded(): bool
    {
        return $this->detailsCertificateWillBeAwarded;
    }

    public function setDetailsCertificateWillBeAwarded(bool $detailsCertificateWillBeAwarded): self
    {
        $this->detailsCertificateWillBeAwarded = $detailsCertificateWillBeAwarded;

        return $this;
    }

    public function getAvailabilityNotes(): ?string
    {
        return $this->availabilityNotes;
    }

    public function setAvailabilityNotes(?string $availabilityNotes): self
    {
        $this->availabilityNotes = $availabilityNotes;

        return $this;
    }

    public function getGeneralLocation(): string
    {
        return $this->generalLocation;
    }

    public function setGeneralLocation(string $generalLocation): self
    {
        $this->generalLocation = $generalLocation;

        return $this;
    }

    public function getGeneralEvaluation(): ?string
    {
        return $this->generalEvaluation;
    }

    public function setGeneralEvaluation(?string $generalEvaluation): self
    {
        $this->generalEvaluation = $generalEvaluation;

        return $this;
    }

    public function getEmployeeIds(): array
    {
        return $this->employeeIds;
    }

    public function setEmployeeIds(array $employeeIds): self
    {
        $this->employeeIds = $employeeIds;

        return $this;
    }

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function setAvailability(?Availability $availability): self
    {
        $this->availability = $availability;

        return $this;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getMinParticipations(): ?int
    {
        return $this->minParticipations;
    }

    public function setMinParticipations(?int $minParticipations): self
    {
        $this->minParticipations = $minParticipations;

        return $this;
    }

    public function getMaxParticipations(): ?int
    {
        return $this->maxParticipations;
    }

    public function setMaxParticipations(?int $maxParticipations): self
    {
        $this->maxParticipations = $maxParticipations;

        return $this;
    }

}
