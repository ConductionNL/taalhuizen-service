<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use App\Repository\DossierRepository;
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
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=GroupRepository::class)
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
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typeCourse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outComesGoal;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $detailsIsFormal;

    /**
     * @ORM\Column(type="integer", length=255)
     */
    private $detailsTotalClassHours;

    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $availabilityNotes;

    /**
     * @ORM\Column(type="string", length=255)
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
     * @ORM\Column(type="string", length=255)
     */
    private $outComesTopic;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outComesTopicOther;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $outComesApplication;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outComesApplicationOther;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outComesLevelOther;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $iscedEducationLevelCode;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $outComesLevel;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $availability = [];

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTypeCourse(): ?string
    {
        return $this->typeCourse;
    }

    public function setTypeCourse(string $typeCourse): self
    {
        $this->typeCourse = $typeCourse;

        return $this;
    }

    public function getOutComesGoal(): ?string
    {
        return $this->outComesGoal;
    }

    public function setOutComesGoal(string $outComesGoal): self
    {
        $this->outComesGoal = $outComesGoal;

        return $this;
    }

    public function getDetailsIsFormal(): ?string
    {
        return $this->detailsIsFormal;
    }

    public function setDetailsIsFormal(string $detailsIsFormal): self
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

    public function setDetailsCertificateWillBeAwarded(bool $detailsCertificateWillBeAwarded): self
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

    public function setGeneralLocation(string $generalLocation): self
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

    public function setOutComesTopic(string $outComesTopic): self
    {
        $this->outComesTopic = $outComesTopic;

        return $this;
    }

    public function getOutComesTopicOther(): ?string
    {
        return $this->outComesTopicOther;
    }

    public function setOutComesTopicOther(string $outComesTopicOther): self
    {
        $this->outComesTopicOther = $outComesTopicOther;

        return $this;
    }

    public function getOutComesApplication(): ?string
    {
        return $this->outComesApplication;
    }

    public function setOutComesApplication(string $outComesApplication): self
    {
        $this->outComesApplication = $outComesApplication;

        return $this;
    }

    public function getIscedEducationLevelCode(): ?string
    {
        return $this->iscedEducationLevelCode;
    }

    public function setIscedEducationLevelCode(string $iscedEducationLevelCode): self
    {
        $this->iscedEducationLevelCode = $iscedEducationLevelCode;

        return $this;
    }

    public function getOutComesApplicationOther(): ?string
    {
        return $this->outComesApplicationOther;
    }

    public function setOutComesApplicationOther(string $outComesApplicationOther): self
    {
        $this->outComesApplicationOther = $outComesApplicationOther;

        return $this;
    }

    public function getOutComesLevelOther(): ?string
    {
        return $this->outComesLevelOther;
    }

    public function setOutComesLevelOther(string $outComesLevelOther): self
    {
        $this->outComesLevelOther = $outComesLevelOther;
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

    public function setOutComesLevel(string $outComesLevel): self
    {
        $this->outComesLevel = $outComesLevel;

        return $this;
    }

}
