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
 *     },
 *     graphql={
 *          "item_query" = {
 *              "item_query" = TestResultQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = TestResultQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = TestResultMutationResolver::class,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = TestResultMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = TestResultMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     },
 * )
 * @ApiFilter(SearchFilter::class, properties={"participationId": "exact"})
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
    private UuidInterface $id;

    /**
     * @var String|null The id of a participation this TestResult is connected to.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private ?string $participationId;

    /**
     * @var String|null The learningNeedOutCome of this TestResult.
     *
     * @Groups({"write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?string $learningNeedOutCome;

    /**
     * @var String|null The used exam for this TestResult.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private ?string $usedExam;

    /**
     * @var String|null The date of the exam that this TestResult is a result of.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private ?string $examDate;

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

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
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

    public function getLearningNeedOutCome(): ?string
    {
        return $this->learningNeedOutCome;
    }

    public function setLearningNeedOutCome(string $learningNeedOutCome): self
    {
        $this->learningNeedOutCome = $learningNeedOutCome;

        return $this;
    }

    public function getUsedExam(): ?string
    {
        return $this->usedExam;
    }

    public function setUsedExam(string $usedExam): self
    {
        $this->usedExam = $usedExam;

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
