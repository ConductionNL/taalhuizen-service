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
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=TestResultRepository::class)
 */
class TestResult
{
    /**
     * @var UuidInterface The UUID identifier of this resource
     *
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private UuidInterface $id;

    /**
     * @var String The id of a participation this TestResult is connected to.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\NotNull
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $participationId;

    /**
     * @var LearningNeedOutCome|null The learningNeedOutCome of this TestResult.
     *
     * @Groups({"write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?LearningNeedOutCome $learningNeedOutCome;

    /**
     * @var String The used exam for this TestResult.
     *
     * @Assert\NotNull
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $usedExam;

    /**
     * @var String The date of the exam that this TestResult is a result of.
     *
     * @Assert\NotNull
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $examDate;

    /**
     * @var String|null A memo/note for this TestResult.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $examMemo;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getParticipationId(): string
    {
        return $this->participationId;
    }

    public function setParticipationId(string $participationId): self
    {
        $this->participationId = $participationId;

        return $this;
    }

    public function getLearningNeedOutCome(): LearningNeedOutCome
    {
        return $this->learningNeedOutCome;
    }

    public function setLearningNeedOutCome(?LearningNeedOutCome $learningNeedOutCome): self
    {
        $this->learningNeedOutCome = $learningNeedOutCome;

        return $this;
    }

    public function getUsedExam(): string
    {
        return $this->usedExam;
    }

    public function setUsedExam(string $usedExam): self
    {
        $this->usedExam = $usedExam;

        return $this;
    }

    public function getExamDate(): string
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
