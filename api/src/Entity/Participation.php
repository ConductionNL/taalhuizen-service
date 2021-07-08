<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use App\Resolver\ParticipationMutationResolver;
use App\Resolver\ParticipationQueryCollectionResolver;
use App\Resolver\ParticipationQueryItemResolver;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiProperty;


/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=ParticipationRepository::class)
 */
class Participation
{
//   Id of the participation, was called in the graphql-schema 'participationId', changed to 'id'
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
     * @var String|null A contact component organization id of this Participation.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $organizationId;

    /**
     * @var String|null The organization name of this Participation.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $organizationName;

//   Organization note of the participation, was called in the graphql-schema 'aanbiederNote', changed to 'organizationNote'
    /**
     * @var ?string Organization note of this participation
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $organizationNote;

    /**
     * @var ?LearningNeedOutCome The learning need out come of this participation.
     *
     * @Groups({"read","write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?LearningNeedOutCome $learningNeedOutCome;

    /**
     * @var DateTimeInterface|null The start date of this participation.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $startDate;

    /**
     * @var DateTimeInterface|null The end date of this participation.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $endDate;

    /**
     * @var ?string Offer name of this participation
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $offerName;

    /**
     * @var ?string Offer course of this participation.
     *
     * @Assert\Choice({"LANGUAGE", "MATH", "DIGITAL", "OTHER"})
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"LANGUAGE", "MATH", "DIGITAL", "OTHER"},
     *             "example"="LANGUAGE"
     *         }
     *     }
     * )
     */
    private ?string $offerCourse;

    /**
     * @var ?string Details engagements of this participation
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $detailsEngagements;

    /**
     * @var string The id of the LearningNeed connected to this Participation.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $learningNeedId;

    /**
     * @var ?string Presence engagements of this participation.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $presenceEngagements;

    /**
     * @var ?DateTime Presence start date of this participation.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $presenceStartDate;

    /**
     * @var ?DateTime Presence end date of this participation.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $presenceEndDate;

    /**
     * @var ?string Currently following course professionalism of this Employee.
     *
     * @Assert\Choice({"MOVED", "JOB", "ILLNESS", "DEATH", "COMPLETED_SUCCESSFULLY", "FAMILY_CIRCUMSTANCES", "DOES_NOT_MEET_EXPECTATIONS", "OTHER"})
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"MOVED", "JOB", "ILLNESS", "DEATH", "COMPLETED_SUCCESSFULLY", "FAMILY_CIRCUMSTANCES", "DOES_NOT_MEET_EXPECTATIONS", "OTHER"},
     *             "example"="MOVED"
     *         }
     *     }
     * )
     */
    private ?string $presenceEndParticipationReason;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getOrganizationId(): ?string
    {
        return $this->organizationId;
    }

    public function setOrganizationId(?string $organizationId): self
    {
        $this->organizationId = $organizationId;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(?string $organizationName): self
    {
        $this->organizationName = $organizationName;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getOrganizationNote(): ?string
    {
        return $this->organizationNote;
    }

    public function setOrganizationNote(?string $organizationNote): self
    {
        $this->organizationNote = $organizationNote;

        return $this;
    }

    public function getOfferName(): ?string
    {
        return $this->offerName;
    }

    public function setOfferName(?string $offerName): self
    {
        $this->offerName = $offerName;

        return $this;
    }

    public function getOfferCourse(): ?string
    {
        return $this->offerCourse;
    }

    public function setOfferCourse(?string $offerCourse): self
    {
        $this->offerCourse = $offerCourse;

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

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getDetailsEngagements(): ?string
    {
        return $this->detailsEngagements;
    }

    public function setDetailsEngagements(?string $detailsEngagements): self
    {
        $this->detailsEngagements = $detailsEngagements;

        return $this;
    }

    public function getPresenceEngagements(): ?string
    {
        return $this->presenceEngagements;
    }

    public function setPresenceEngagements(?string $presenceEngagements): self
    {
        $this->presenceEngagements = $presenceEngagements;

        return $this;
    }

    public function getPresenceStartDate(): ?\DateTimeInterface
    {
        return $this->presenceStartDate;
    }

    public function setPresenceStartDate(?\DateTimeInterface $presenceStartDate): self
    {
        $this->presenceStartDate = $presenceStartDate;

        return $this;
    }

    public function getPresenceEndDate(): ?\DateTimeInterface
    {
        return $this->presenceEndDate;
    }

    public function setPresenceEndDate(?\DateTimeInterface $presenceEndDate): self
    {
        $this->presenceEndDate = $presenceEndDate;

        return $this;
    }

    public function getPresenceEndParticipationReason(): ?string
    {
        return $this->presenceEndParticipationReason;
    }

    public function setPresenceEndParticipationReason(?string $presenceEndParticipationReason): self
    {
        $this->presenceEndParticipationReason = $presenceEndParticipationReason;

        return $this;
    }

    public function getLearningNeedId(): string
    {
        return $this->learningNeedId;
    }

    public function setLearningNeedId(string $learningNeedId): self
    {
        $this->learningNeedId = $learningNeedId;

        return $this;
    }
}
