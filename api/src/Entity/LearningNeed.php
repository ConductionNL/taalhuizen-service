<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\LearningNeedRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=LearningNeedRepository::class)
 */
class LearningNeed
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
     * @ORM\Column(type="string", length=255)
     */
    private $learningNeedDescription;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $learningNeedMotivation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $desiredOutComesGoal;

    /**
     * @Assert\Choice({"DUTCH_READING", "DUTCH_WRITING", "MATH_NUMBERS", "MATH_PROPORTION", "MATH_GEOMETRY", "MATH_LINKS", "DIGITAL_USING_ICT_SYSTEMS", "DIGITAL_SEARCHING_INFORMATION", "DIGITAL_PROCESSING_INFORMATION", "DIGITAL_COMMUNICATION", "KNOWLEDGE", "SKILLS", "ATTITUDE", "BEHAVIOUR", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $desiredOutComesTopic;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $desiredOutComesTopicOther;

    /**
     * @Assert\Choice({"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $desiredOutComesApplication;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $desiredOutComesApplicationOther;

    /**
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $desiredOutComesLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $desiredOutComesLevelOther;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $offerDesiredOffer;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $offerAdvisedOffer;

    /**
     * @Assert\Choice({"NO", "YES_DISTANCE", "YES_WAITINGLIST", "YES_OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $offerDifference;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offerDifferenceOther;

    /**
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $offerEngagements;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $participations = [];

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getLearningNeedDescription(): ?string
    {
        return $this->learningNeedDescription;
    }

    public function setLearningNeedDescription(string $learningNeedDescription): self
    {
        $this->learningNeedDescription = $learningNeedDescription;

        return $this;
    }

    public function getLearningNeedMotivation(): ?string
    {
        return $this->learningNeedMotivation;
    }

    public function setLearningNeedMotivation(string $learningNeedMotivation): self
    {
        $this->learningNeedMotivation = $learningNeedMotivation;

        return $this;
    }

    public function getDesiredOutComesGoal(): ?string
    {
        return $this->desiredOutComesGoal;
    }

    public function setDesiredOutComesGoal(string $desiredOutComesGoal): self
    {
        $this->desiredOutComesGoal = $desiredOutComesGoal;

        return $this;
    }

    public function getDesiredOutComesTopic(): ?string
    {
        return $this->desiredOutComesTopic;
    }

    public function setDesiredOutComesTopic(string $desiredOutComesTopic): self
    {
        $this->desiredOutComesTopic = $desiredOutComesTopic;

        return $this;
    }

    public function getDesiredOutComesTopicOther(): ?string
    {
        return $this->desiredOutComesTopicOther;
    }

    public function setDesiredOutComesTopicOther(string $desiredOutComesTopicOther): self
    {
        $this->desiredOutComesTopicOther = $desiredOutComesTopicOther;

        return $this;
    }

    public function getDesiredOutComesApplication(): ?string
    {
        return $this->desiredOutComesApplication;
    }

    public function setDesiredOutComesApplication(string $desiredOutComesApplication): self
    {
        $this->desiredOutComesApplication = $desiredOutComesApplication;

        return $this;
    }

    public function getDesiredOutComesApplicationOther(): ?string
    {
        return $this->desiredOutComesApplicationOther;
    }

    public function setDesiredOutComesApplicationOther(string $desiredOutComesApplicationOther): self
    {
        $this->desiredOutComesApplicationOther = $desiredOutComesApplicationOther;

        return $this;
    }

    public function getDesiredOutComesLevel(): ?string
    {
        return $this->desiredOutComesLevel;
    }

    public function setDesiredOutComesLevel(string $desiredOutComesLevel): self
    {
        $this->desiredOutComesLevel = $desiredOutComesLevel;

        return $this;
    }

    public function getDesiredOutComesLevelOther(): ?string
    {
        return $this->desiredOutComesLevelOther;
    }

    public function setDesiredOutComesLevelOther(string $desiredOutComesLevelOther): self
    {
        $this->desiredOutComesLevelOther = $desiredOutComesLevelOther;

        return $this;
    }

    public function getOfferDesiredOffer(): ?string
    {
        return $this->offerDesiredOffer;
    }

    public function setOfferDesiredOffer(string $offerDesiredOffer): self
    {
        $this->offerDesiredOffer = $offerDesiredOffer;

        return $this;
    }

    public function getOfferAdvisedOffer(): ?string
    {
        return $this->offerAdvisedOffer;
    }

    public function setOfferAdvisedOffer(string $offerAdvisedOffer): self
    {
        $this->offerAdvisedOffer = $offerAdvisedOffer;

        return $this;
    }

    public function getOfferDifference(): ?string
    {
        return $this->offerDifference;
    }

    public function setOfferDifference(string $offerDifference): self
    {
        $this->offerDifference = $offerDifference;

        return $this;
    }

    public function getOfferDifferenceOther(): ?string
    {
        return $this->offerDifferenceOther;
    }

    public function setOfferDifferenceOther(string $offerDifferenceOther): self
    {
        $this->offerDifferenceOther = $offerDifferenceOther;

        return $this;
    }

    public function getOfferEngagements(): ?string
    {
        return $this->offerEngagements;
    }

    public function setOfferEngagements(?string $offerEngagements): self
    {
        $this->offerEngagements = $offerEngagements;

        return $this;
    }

    public function getParticipations(): ?array
    {
        return $this->participations;
    }

    public function setParticipations(?array $participations): self
    {
        $this->participations = $participations;

        return $this;
    }
}
