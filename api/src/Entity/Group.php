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
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
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
    private $aanbiederId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $typeCourse;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesGoal;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesTopic;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesTopicOther;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesApplication;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesApplicationOther;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesLevelOther;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $detailsIsFormal;

    /**
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private $detailsTotalClassHours;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $detailsCertificateWillBeAwarded;

    /**
     * @ORM\Column(type="datetime", nullable=true, nullable=true)
     */
    private $detailsStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $detailsEndDate;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $availability = [];

    /**
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $availabilityNotes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $generalLocation;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $generalParticipantsMin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $generalParticipantsMax;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $generalEvaluation;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $aanbiederEmployeeIds = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $groupId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTypeCourse(): ?string
    {
        return $this->typeCourse;
    }

    public function setTypeCourse(?string $typeCourse): self
    {
        $this->typeCourse = $typeCourse;

        return $this;
    }

    public function getOutComesGoal(): ?string
    {
        return $this->outComesGoal;
    }

    public function setOutComesGoal(?string $outComesGoal): self
    {
        $this->outComesGoal = $outComesGoal;

        return $this;
    }

    public function getDetailsIsFormal(): ?string
    {
        return $this->detailsIsFormal;
    }

    public function setDetailsIsFormal(?string $detailsIsFormal): self
    {
        $this->detailsIsFormal = $detailsIsFormal;

        return $this;
    }

    public function getDetailsTotalClassHours(): ?int
    {
        return $this->detailsTotalClassHours;
    }

    public function setDetailsTotalClassHours(?int $detailsTotalClassHours): self
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

    public function getAvailabilityNotes(): ?string
    {
        return $this->availabilityNotes;
    }

    public function setAvailabilityNotes(?string $availabilityNotes): self
    {
        $this->availabilityNotes = $availabilityNotes;

        return $this;
    }

    public function getGeneralLocation(): ?string
    {
        return $this->generalLocation;
    }

    public function setGeneralLocation(?string $generalLocation): self
    {
        $this->generalLocation = $generalLocation;

        return $this;
    }

    public function getGeneralParticipantsMin(): ?int
    {
        return $this->generalParticipantsMin;
    }

    public function setGeneralParticipantsMin(?int $generalParticipantsMin): self
    {
        $this->generalParticipantsMin = $generalParticipantsMin;

        return $this;
    }

    public function getGeneralParticipantsMax(): ?int
    {
        return $this->generalParticipantsMax;
    }

    public function setGeneralParticipantsMax(?int $generalParticipantsMax): self
    {
        $this->generalParticipantsMax = $generalParticipantsMax;

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

    public function getAanbiederEmployeeIds(): ?array
    {
        return $this->aanbiederEmployeeIds;
    }

    public function setAanbiederEmployeeIds(?array $aanbiederEmployeeIds): self
    {
        $this->aanbiederEmployeeIds = $aanbiederEmployeeIds;

        return $this;
    }

    public function getOutComesTopic(): ?string
    {
        return $this->outComesTopic;
    }

    public function setOutComesTopic(?string $outComesTopic): self
    {
        $this->outComesTopic = $outComesTopic;

        return $this;
    }

    public function getOutComesTopicOther(): ?string
    {
        return $this->outComesTopicOther;
    }

    public function setOutComesTopicOther(?string $outComesTopicOther): self
    {
        $this->outComesTopicOther = $outComesTopicOther;

        return $this;
    }

    public function getOutComesApplication(): ?string
    {
        return $this->outComesApplication;
    }

    public function setOutComesApplication(?string $outComesApplication): self
    {
        $this->outComesApplication = $outComesApplication;

        return $this;
    }

    public function getOutComesApplicationOther(): ?string
    {
        return $this->outComesApplicationOther;
    }

    public function setOutComesApplicationOther(?string $outComesApplicationOther): self
    {
        $this->outComesApplicationOther = $outComesApplicationOther;

        return $this;
    }

    public function getOutComesLevelOther(): ?string
    {
        return $this->outComesLevelOther;
    }

    public function setOutComesLevelOther(?string $outComesLevelOther): self
    {
        $this->outComesLevelOther = $outComesLevelOther;

        return $this;
    }

    public function getAvailability(): ?array
    {
        return $this->availability;
    }

    public function setAvailability(?array $availability): self
    {
        $this->availability = $availability;

        return $this;
    }

    public function getOutComesLevel(): ?string
    {
        return $this->outComesLevel;
    }

    public function setOutComesLevel(?string $outComesLevel): self
    {
        $this->outComesLevel = $outComesLevel;

        return $this;
    }

    public function getAanbiederId(): ?string
    {
        return $this->aanbiederId;
    }

    public function setAanbiederId(?string $aanbiederId): self
    {
        $this->aanbiederId = $aanbiederId;

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
}
