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
     * @ORM\Column(type="string", length=255)
     */
    private $provider;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offerName;

    /**
     * @Assert\Choice({"Taal", "Rekenen", "Digitale vaardigheden", "Overige"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $courseType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $learningNeedVerb;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $learningNeedSubject;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $learningNeedApplication;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $learningNeedLevel;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $formality;

    /**
     * @Assert\Choice({"Individueel", "In een groep"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $groupFormation;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hoursPerParticipation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
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
    private $agreementsNote;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

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

    public function getCourseType(): ?string
    {
        return $this->courseType;
    }

    public function setCourseType(?string $courseType): self
    {
        $this->courseType = $courseType;

        return $this;
    }

    public function getLearningNeedVerb(): ?string
    {
        return $this->learningNeedVerb;
    }

    public function setLearningNeedVerb(?string $learningNeedVerb): self
    {
        $this->learningNeedVerb = $learningNeedVerb;

        return $this;
    }

    public function getLearningNeedSubject(): ?string
    {
        return $this->learningNeedSubject;
    }

    public function setLearningNeedSubject(?string $learningNeedSubject): self
    {
        $this->learningNeedSubject = $learningNeedSubject;

        return $this;
    }

    public function getLearningNeedApplication(): ?string
    {
        return $this->learningNeedApplication;
    }

    public function setLearningNeedApplication(?string $learningNeedApplication): self
    {
        $this->learningNeedApplication = $learningNeedApplication;

        return $this;
    }

    public function getLearningNeedLevel(): ?string
    {
        return $this->learningNeedLevel;
    }

    public function setLearningNeedLevel(?string $learningNeedLevel): self
    {
        $this->learningNeedLevel = $learningNeedLevel;

        return $this;
    }

    public function getFormality(): ?bool
    {
        return $this->formality;
    }

    public function setFormality(?bool $formality): self
    {
        $this->formality = $formality;

        return $this;
    }

    public function getGroupFormation(): ?string
    {
        return $this->groupFormation;
    }

    public function setGroupFormation(?string $groupFormation): self
    {
        $this->groupFormation = $groupFormation;

        return $this;
    }

    public function getHoursPerParticipation(): ?int
    {
        return $this->hoursPerParticipation;
    }

    public function setHoursPerParticipation(?int $hoursPerParticipation): self
    {
        $this->hoursPerParticipation = $hoursPerParticipation;

        return $this;
    }

    public function getCertificate(): ?bool
    {
        return $this->certificate;
    }

    public function setCertificate(?bool $certificate): self
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

    public function getAgreementsNote(): ?string
    {
        return $this->agreementsNote;
    }

    public function setAgreementsNote(?string $agreementsNote): self
    {
        $this->agreementsNote = $agreementsNote;

        return $this;
    }
}
