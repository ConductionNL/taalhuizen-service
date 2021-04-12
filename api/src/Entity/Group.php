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
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $typeOfCourse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $verb;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $formality;

    /**
     * @ORM\Column(type="integer", length=255)
     */
    private $classHours;

    /**
     * @ORM\Column(type="boolean")
     */
    private $certificate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $availabilityNote;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $location;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $minParticipants;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxParticipants;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $evaluation;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $mentors = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $topic;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $application;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $iscedEducationLevelCode;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $availability = [];

    public function getId(): ?int
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

    public function getTypeOfCourse(): ?string
    {
        return $this->typeOfCourse;
    }

    public function setTypeOfCourse(string $typeOfCourse): self
    {
        $this->typeOfCourse = $typeOfCourse;

        return $this;
    }

    public function getVerb(): ?string
    {
        return $this->verb;
    }

    public function setVerb(string $verb): self
    {
        $this->verb = $verb;

        return $this;
    }

    public function getFormality(): ?string
    {
        return $this->formality;
    }

    public function setFormality(string $formality): self
    {
        $this->formality = $formality;

        return $this;
    }

    public function getClassHours(): ?int
    {
        return $this->classHours;
    }

    public function setClassHours(?int $classHours): self
    {
        $this->classHours = $classHours;

        return $this;
    }

    public function getCertificate(): ?bool
    {
        return $this->certificate;
    }

    public function setCertificate(bool $certificate): self
    {
        $this->certificate = $certificate;

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

    public function getAvailabilityNote(): ?string
    {
        return $this->availabilityNote;
    }

    public function setAvailabilityNote(?string $availabilityNote): self
    {
        $this->availabilityNote = $availabilityNote;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getMinParticipants(): ?int
    {
        return $this->minParticipants;
    }

    public function setMinParticipants(?int $minParticipants): self
    {
        $this->minParticipants = $minParticipants;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): self
    {
        $this->maxParticipants = $maxParticipants;

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

    public function getMentors(): ?array
    {
        return $this->mentors;
    }

    public function setMentors(?array $mentors): self
    {
        $this->mentors = $mentors;

        return $this;
    }

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function getApplication(): ?string
    {
        return $this->application;
    }

    public function setApplication(string $application): self
    {
        $this->application = $application;

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

    public function getAvailability(): ?array
    {
        return $this->availability;
    }

    public function setAvailability(?array $availability): self
    {
        $this->availability = $availability;

        return $this;
    }

}
