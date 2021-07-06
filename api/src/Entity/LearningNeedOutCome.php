<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\TestResultRepository;
use App\Resolver\TestResultMutationResolver;
use App\Resolver\TestResultQueryCollectionResolver;
use App\Resolver\TestResultQueryItemResolver;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=LearningNeedOutComeRepository::class)
 */
class LearningNeedOutCome
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
    private UuidInterface $id;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private ?string $goal;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"DUTCH_READING", "DUTCH_WRITING", "MATH_NUMBERS", "MATH_PROPORTION", "MATH_GEOMETRY", "MATH_LINKS", "DIGITAL_USING_ICT_SYSTEMS", "DIGITAL_SEARCHING_INFORMATION", "DIGITAL_PROCESSING_INFORMATION", "DIGITAL_COMMUNICATION", "KNOWLEDGE", "SKILLS", "ATTITUDE", "BEHAVIOUR", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private ?string $topic;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $topicOther;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private ?string $application;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $applicationOther;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private ?string $level;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $levelOther;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isFormal;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"INDIVIDUALLY", "IN_A_GROUP"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $groupFormation;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $totalClassHours;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $certificateWillBeAwarded;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $startDate;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $endDate;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getGoal(): ?string
    {
        return $this->goal;
    }

    public function setGoal(string $goal): self
    {
        $this->goal = $goal;

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

    public function getTopicOther(): ?string
    {
        return $this->topicOther;
    }

    public function setTopicOther(?string $topicOther): self
    {
        $this->topicOther = $topicOther;

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

    public function getApplicationOther(): ?string
    {
        return $this->applicationOther;
    }

    public function setApplicationOther(?string $applicationOther): self
    {
        $this->applicationOther = $applicationOther;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevelOther(): ?string
    {
        return $this->levelOther;
    }

    public function setLevelOther(?string $levelOther): self
    {
        $this->levelOther = $levelOther;

        return $this;
    }

    public function getIsFormal(): ?bool
    {
        return $this->isFormal;
    }

    public function setIsFormal(?bool $isFormal): self
    {
        $this->isFormal = $isFormal;

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

    public function getTotalClassHours(): ?float
    {
        return $this->totalClassHours;
    }

    public function setTotalClassHours(?float $totalClassHours): self
    {
        $this->totalClassHours = $totalClassHours;

        return $this;
    }

    public function getCertificateWillBeAwarded(): ?bool
    {
        return $this->certificateWillBeAwarded;
    }

    public function setCertificateWillBeAwarded(?bool $certificateWillBeAwarded): self
    {
        $this->certificateWillBeAwarded = $certificateWillBeAwarded;

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
}
