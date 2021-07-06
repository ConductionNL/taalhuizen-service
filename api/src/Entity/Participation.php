<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\ParticipationRepository;
use App\Resolver\ParticipationMutationResolver;
use App\Resolver\ParticipationQueryCollectionResolver;
use App\Resolver\ParticipationQueryItemResolver;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
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
 *              "item_query" = ParticipationQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = ParticipationQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "addMentorTo" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"participationId"={"type" = "ID!"}, "aanbiederEmployeeId"={"type" = "ID!"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "removeMentorFrom" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"participationId"={"type" = "ID!"}, "aanbiederEmployeeId"={"type" = "ID!"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "updateMentor" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {
 *                  "participationId"={"type" = "ID!"},
 *                  "presenceEngagements"={"type" = "String"},
 *                  "presenceStartDate"={"type" = "String"},
 *                  "presenceEndDate"={"type" = "String"},
 *                  "presenceEndParticipationReason"={"type" = "String"}
 *              },
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "addGroupTo" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"participationId"={"type" = "ID!"}, "groupId"={"type" = "ID!"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "updateGroup" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {
 *                  "participationId"={"type" = "ID!"},
 *                  "presenceEngagements"={"type" = "String"},
 *                  "presenceStartDate"={"type" = "String"},
 *                  "presenceEndDate"={"type" = "String"},
 *                  "presenceEndParticipationReason"={"type" = "String"}
 *              },
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "removeGroupFrom" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"participationId"={"type" = "ID!"}, "groupId"={"type" = "ID!"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     }
 * )
 * @ApiFilter(SearchFilter::class, properties={"learningNeedId": "exact"})
 * @ORM\Entity(repositoryClass=ParticipationRepository::class)
 */
class Participation
{
//   Id of the participation, was called in the graphql-schema 'participationId', changed to 'id'
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

// @todo outcomes properties toevoegen
    /**
     * @var ?string Status of this participation. **ACTIVE**, **COMPLETED**, **REFERRED**
     *
     * @Assert\Choice({"ACTIVE", "COMPLETED", "REFERRED"})
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $status;

//   Organization of the participation, was called in the graphql-schema 'aanbiederId' and 'aanbiederName', changed to 'organization'(Organization entity) related to schema.org
    /**
     * @var ?Organization Organization of this participation
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Organization::class, cascade={"persist", "remove"})
     */
    private ?Organization $organization;

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
     * @var ?string Offer course of this participation. **MATH**, **DIGITAL**, **OTHER**
     *
     * @Assert\Choice({"LANGUAGE", "MATH", "DIGITAL", "OTHER"})
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $offerCourse;

    /**
     * @var ?bool Details is formal of this participation.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $detailsIsFormal;

    /**
     * @var ?string Details group formation of this participation. **INDIVIDUALLY**, **IN_A_GROUP**
     *
     * @Assert\Choice({"INDIVIDUALLY", "IN_A_GROUP"})
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $detailsGroupFormation;

    /**
     * @var ?float Details total class hours of this participation.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $detailsTotalClassHours;

    /**
     * @var ?bool Details certificate will be awarded of this participation.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $detailsCertificateWillBeAwarded;

    /**
     * @var ?DateTime Details start date of this participation.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $detailsStartDate;

    /**
     * @var ?DateTime Details end date of this participation.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $detailsEndDate;

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

//   Learning need of the participation, was called in the graphql-schema 'learningNeedId' and 'learningNeedUrl', changed to 'LearningNeed'
    /**
     * @var ?LearningNeed LearningNeed of this participation
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=LearningNeed::class, cascade={"persist", "remove"})
     */
    private ?LearningNeed $learningNeed;

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
     * @var ?string Currently following course professionalism of this Employee. **MOVED**, **JOB**, **ILLNESS**, **DEATH**, **COMPLETED_SUCCESSFULLY**, **FAMILY_CIRCUMSTANCES**, **DOES_NOT_MEET_EXPECTATIONS**, **OTHER**
     *
     * @Assert\Choice({"MOVED", "JOB", "ILLNESS", "DEATH", "COMPLETED_SUCCESSFULLY", "FAMILY_CIRCUMSTANCES", "DOES_NOT_MEET_EXPECTATIONS", "OTHER"})
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $presenceEndParticipationReason;

    /**
     * @var ?string Employee id of this participation
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $employeeId;

    /**
     * @var ?string Group id of this participation
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $groupId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

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

    public function getDetailsIsFormal(): ?bool
    {
        return $this->detailsIsFormal;
    }

    public function setDetailsIsFormal(?bool $detailsIsFormal): self
    {
        $this->detailsIsFormal = $detailsIsFormal;

        return $this;
    }

    public function getDetailsGroupFormation(): ?string
    {
        return $this->detailsGroupFormation;
    }

    public function setDetailsGroupFormation(?string $detailsGroupFormation): self
    {
        $this->detailsGroupFormation = $detailsGroupFormation;

        return $this;
    }

    public function getDetailsTotalClassHours(): ?float
    {
        return $this->detailsTotalClassHours;
    }

    public function setDetailsTotalClassHours(?float $detailsTotalClassHours): self
    {
        $this->detailsTotalClassHours = $detailsTotalClassHours;

        return $this;
    }

    public function getDetailsCertificateWillBeAwarded(): ?bool
    {
        return $this->detailsCertificateWillBeAwarded;
    }

    public function setDetailsCertificateWillBeAwarded(?bool $detailsCertificateWillBeAwarded): self
    {
        $this->detailsCertificateWillBeAwarded = $detailsCertificateWillBeAwarded;

        return $this;
    }

    public function getDetailsStartDate(): ?\DateTimeInterface
    {
        return $this->detailsStartDate;
    }

    public function setDetailsStartDate(?\DateTimeInterface $detailsStartDate): self
    {
        $this->detailsStartDate = $detailsStartDate;

        return $this;
    }

    public function getDetailsEndDate(): ?\DateTimeInterface
    {
        return $this->detailsEndDate;
    }

    public function setDetailsEndDate(?\DateTimeInterface $detailsEndDate): self
    {
        $this->detailsEndDate = $detailsEndDate;

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

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?string $employeeId): self
    {
        $this->employeeId = $employeeId;

        return $this;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    public function getLearningNeed(): ?LearningNeed
    {
        return $this->learningNeed;
    }

    public function setLearningNeed(?LearningNeed $learningNeed): self
    {
        $this->learningNeed = $learningNeed;

        return $this;
    }
}
