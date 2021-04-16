<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *  collectionOperations={
 *          "get",
 *          "get_participation"={
 *              "method"="GET",
 *              "path"="/participations/{id}",
 *              "swagger_context" = {
 *                  "summary"="Gets a specific participation",
 *                  "description"="Returns a participation"
 *              }
 *          },
 *          "delete_participation"={
 *              "method"="GET",
 *              "path"="/participations/{id}/delete",
 *              "swagger_context" = {
 *                  "summary"="Deletes a specific participation",
 *                  "description"="Returns true if this participation was deleted"
 *              }
 *          },
 *          "post"
 *     },
 * )
 * @ORM\Entity(repositoryClass=ParticipationRepository::class)
 */
class Participation
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
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederName;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederNote;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offerName;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"LANGUAGE", "MATH", "DIGITAL", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offerCourse;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesGoal;
    /**
     * @Groups({"write"})
     * @Assert\Choice({"DUTCH_READING", "DUTCH_WRITING", "MATH_NUMBERS", "MATH_PROPORTION", "MATH_GEOMETRY", "MATH_LINKS", "DIGITAL_USING_ICT_SYSTEMS", "DIGITAL_SEARCHING_INFORMATION", "DIGITAL_PROCESSING_INFORMATION", "DIGITAL_COMMUNICATION", "KNOWLEDGE", "SKILLS", "ATTITUDE", "BEHAVIOUR", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesTopic;

    /**
     * @Groups({"write"})
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
     * @Groups({"write"})
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
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesLevelOther;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $detailsIsFormal;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"INDIVIDUALLY", "IN_A_GROUP"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $detailsGroupFormation;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="float", nullable=true)
     */
    private $detailsTotalClassHours;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $detailsCertificateWillBeAwarded;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $detailsStartDate;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $detailsEndDate;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $detailsEngagements;

    /**
     *
     * @Assert\NotNull
     * @ORM\Column(type="string", length=255)
     */
    private $learningNeedId;

    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * @var string The url of the objectEntity of an eav/learning_need '@eav'.
     *
     * @Groups({"write"})
     * @Assert\Url
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $learningNeedUrl;

    public function getId(): UuidInterface
    {
        return $this->id;
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

    public function getAanbiederName(): ?string
    {
        return $this->aanbiederName;
    }

    public function setAanbiederName(?string $aanbiederName): self
    {
        $this->aanbiederName = $aanbiederName;

        return $this;
    }

    public function getAanbiederNote(): ?string
    {
        return $this->aanbiederNote;
    }

    public function setAanbiederNote(?string $aanbiederNote): self
    {
        $this->aanbiederNote = $aanbiederNote;

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

    public function getOutComesGoal(): ?string
    {
        return $this->outComesGoal;
    }

    public function setOutComesGoal(?string $outComesGoal): self
    {
        $this->outComesGoal = $outComesGoal;

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

    public function getOutComesLevel(): ?string
    {
        return $this->outComesLevel;
    }

    public function setOutComesLevel(?string $outComesLevel): self
    {
        $this->outComesLevel = $outComesLevel;

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

    public function getLearningNeedId(): ?string
    {
        return $this->learningNeedId;
    }

    public function setLearningNeedId(string $learningNeedId): self
    {
        $this->learningNeedId = $learningNeedId;

        return $this;
    }
}
