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
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $motivation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $learningNeedVerb;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $learningNeedSubject;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $learningNeedApplication;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $learningNeedLevel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $desiredOffer;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $advisedOffer;

    /**
     * @Assert\Choice({"Nee, er is geen verschil", "Ja, want: niet aangeboden binnen bereisbare afstand", "Ja, want: wachtlijst", "Ja, want:anders"})
     * @ORM\Column(type="string", length=255)
     */
    private $differenceDesireAdvice;

    /**
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $agreementsNote;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(string $motivation): self
    {
        $this->motivation = $motivation;

        return $this;
    }

    public function getLearningNeedVerb(): ?string
    {
        return $this->learningNeedVerb;
    }

    public function setLearningNeedVerb(string $learningNeedVerb): self
    {
        $this->learningNeedVerb = $learningNeedVerb;

        return $this;
    }

    public function getLearningNeedSubject(): ?string
    {
        return $this->learningNeedSubject;
    }

    public function setLearningNeedSubject(string $learningNeedSubject): self
    {
        $this->learningNeedSubject = $learningNeedSubject;

        return $this;
    }

    public function getLearningNeedApplication(): ?string
    {
        return $this->learningNeedApplication;
    }

    public function setLearningNeedApplication(string $learningNeedApplication): self
    {
        $this->learningNeedApplication = $learningNeedApplication;

        return $this;
    }

    public function getLearningNeedLevel(): ?string
    {
        return $this->learningNeedLevel;
    }

    public function setLearningNeedLevel(string $learningNeedLevel): self
    {
        $this->learningNeedLevel = $learningNeedLevel;

        return $this;
    }

    public function getDesiredOffer(): ?string
    {
        return $this->desiredOffer;
    }

    public function setDesiredOffer(string $desiredOffer): self
    {
        $this->desiredOffer = $desiredOffer;

        return $this;
    }

    public function getAdvisedOffer(): ?string
    {
        return $this->advisedOffer;
    }

    public function setAdvisedOffer(string $advisedOffer): self
    {
        $this->advisedOffer = $advisedOffer;

        return $this;
    }

    public function getDifferenceDesireAdvice(): ?string
    {
        return $this->differenceDesireAdvice;
    }

    public function setDifferenceDesireAdvice(string $differenceDesireAdvice): self
    {
        $this->differenceDesireAdvice = $differenceDesireAdvice;

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
