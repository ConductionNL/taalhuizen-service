<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource()
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederNote;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offerName;

    /**
     * @Assert\Choice({"LANGUAGE", "MATH", "DIGITAL", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offerCourse;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesGoal;
    /**
     * @Assert\Choice({"DUTCH_READING", "DUTCH_WRITING", "MATH_NUMBERS", "MATH_PROPORTION", "MATH_GEOMETRY", "MATH_LINKS", "DIGITAL_USING_ICT_SYSTEMS", "DIGITAL_SEARCHING_INFORMATION", "DIGITAL_PROCESSING_INFORMATION", "DIGITAL_COMMUNICATION", "KNOWLEDGE", "SKILLS", "ATTITUDE", "BEHAVIOUR", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesTopic;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesTopicOther;

    /**
     * @Assert\Choice({"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesApplication;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesApplicationOther;

    /**
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesLevelOther;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $detailsIsFormal;

    /**
     * @Assert\Choice({"INDIVIDUALLY", "IN_A_GROUP"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $detailsGroupFormation;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $detailsTotalClassHours;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $detailsCertificateWillBeAwarded;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $detailsStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $detailsEndDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $detailsEngagements;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $learningNeedId;

    public function getId(): Uuid
    {
        return $this->id;
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
