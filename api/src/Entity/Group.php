<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\GroupRepository;
use App\Resolver\GroupMutationResolver;
use App\Resolver\GroupQueryCollectionResolver;
use App\Resolver\GroupQueryItemResolver;
use DateTime;
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
 * All properties that the DTO entity Group holds.
 *
 * The main entity associated with this DTO is the edu/Group: https://taalhuizen-bisc.commonground.nu/api/v1/edu#tag/Group.
 * DTO Group exists of properties based on the following jira epic: https://lifely.atlassian.net/browse/BISC-117.
 * And mainly the following issue: https://lifely.atlassian.net/browse/BISC-146.
 * The learningNeedOutCome input fields are a recurring thing throughout multiple DTO entities, that is why the LearningNeedOutCome Entity was created and used here instead of matching the exact properties in the graphql schema.
 * Notable is that a few properties are renamed here, compared to the graphql schema, this was mostly done for consistency and cleaner names.
 * Translations from Dutch to English, but also shortening names by removing words from the names that had no added value to describe the property itself and that were just added before the name of each property like: 'details' or 'general'.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=GroupRepository::class)
 */
class Group
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
     * @var string|null The id of the cc/organization of a provider
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $providerId;

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
     * @var ?LearningNeedOutCome The learning need out come of this Group.
     *
     * @Groups({"read","write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?LearningNeedOutCome $learningNeedOutCome;

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
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Availability::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
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
     * @var string Location of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $location;

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
     * @var ?string Evaluation of this group.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $evaluation;

    /**
     * @var array Employee ids of this group.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
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

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(?string $providerId): self
    {
        $this->providerId = $providerId;

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

    public function getAvailabilityNotes(): ?string
    {
        return $this->availabilityNotes;
    }

    public function setAvailabilityNotes(?string $availabilityNotes): self
    {
        $this->availabilityNotes = $availabilityNotes;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getEvaluation(): ?string
    {
        return $this->evaluation;
    }

    public function setEvaluation(?string $evaluation): self
    {
        $this->evaluation = $evaluation;

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

    public function getLearningNeedOutCome(): LearningNeedOutCome
    {
        return $this->learningNeedOutCome;
    }

    public function setLearningNeedOutCome(?LearningNeedOutCome $learningNeedOutCome): self
    {
        $this->learningNeedOutCome = $learningNeedOutCome;

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
