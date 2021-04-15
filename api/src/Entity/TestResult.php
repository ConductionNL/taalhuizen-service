<?php

namespace App\Entity;

use App\Repository\TestResultRepository;
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
 * @ORM\Entity(repositoryClass=TestResultRepository::class)
 */
class TestResult
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
    private $participationId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outComesGoal;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outComesTopic;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesTopicOther;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outComesApplication;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesApplicationOther;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $outComesLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesLevelOther;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $examUsedExam;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $examDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $examMemo;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getParticipationId(): ?string
    {
        return $this->participationId;
    }

    public function setParticipationId(string $participationId): self
    {
        $this->participationId = $participationId;

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

    public function setOutComesTopicOther(?string $outComesTopicOther): self
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

    public function setOutComesLevel(string $outComesLevel): self
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

    public function getExamUsedExam(): ?string
    {
        return $this->examUsedExam;
    }

    public function setExamUsedExam(string $examUsedExam): self
    {
        $this->examUsedExam = $examUsedExam;

        return $this;
    }

    public function getExamDate(): ?string
    {
        return $this->examDate;
    }

    public function setExamDate(string $examDate): self
    {
        $this->examDate = $examDate;

        return $this;
    }

    public function getExamMemo(): ?string
    {
        return $this->examMemo;
    }

    public function setExamMemo(?string $examMemo): self
    {
        $this->examMemo = $examMemo;

        return $this;
    }

}
