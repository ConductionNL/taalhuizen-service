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
 * @ApiResource(
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
    private $id;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private $participationId;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private $outComesGoal;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"DUTCH_READING", "DUTCH_WRITING", "MATH_NUMBERS", "MATH_PROPORTION", "MATH_GEOMETRY", "MATH_LINKS", "DIGITAL_USING_ICT_SYSTEMS", "DIGITAL_SEARCHING_INFORMATION", "DIGITAL_PROCESSING_INFORMATION", "DIGITAL_COMMUNICATION", "KNOWLEDGE", "SKILLS", "ATTITUDE", "BEHAVIOUR", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $outComesTopic;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesTopicOther;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $outComesApplication;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesApplicationOther;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     * @ORM\Column(type="string", length=255)
     */
    private $outComesLevel;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $outComesLevelOther;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private $examUsedExam;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private $examDate;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $examMemo;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $examResult;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $testResultId;

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

    public function getExamResult(): ?string
    {
        return $this->examResult;
    }

    public function setExamResult(?string $examResult): self
    {
        $this->examResult = $examResult;

        return $this;
    }

    public function getTestResultId(): ?string
    {
        return $this->testResultId;
    }

    public function setTestResultId(?string $testResultId): self
    {
        $this->testResultId = $testResultId;

        return $this;
    }

}
