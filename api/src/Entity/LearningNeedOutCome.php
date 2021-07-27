<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity LearningNeedOutCome holds.
 *
 * The LearningNeedOutCome input fields are a recurring thing throughout multiple DTO entities like: TestResult, Participation and Group.
 * That is why this LearningNeedOutCome Entity was created. To remove duplicate use of the same properties.
 * Notable is that a few properties are renamed here, compared to the graphql schema, this was mostly done for consistency and cleaner names.
 * Mostly shortening names by removing words from the names that had no added value to describe the property itself and that were just added before the name of each property like: 'outComes'.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={"get"},
 *     collectionOperations={"get"}
 * )
 * @ORM\Entity(repositoryClass=LearningNeedOutComeRepository::class)
 */
class LearningNeedOutCome
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
     * @var string The goal of this LearningNeedOutcome.
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Learn how to work with computers"
     *         }
     *     }
     * )
     */
    private string $goal;

    /**
     * @var string The topic of this LearningNeedOutcome.
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @Assert\Choice({"DUTCH_READING", "DUTCH_WRITING", "MATH_NUMBERS", "MATH_PROPORTION", "MATH_GEOMETRY", "MATH_LINKS", "DIGITAL_USING_ICT_SYSTEMS", "DIGITAL_SEARCHING_INFORMATION", "DIGITAL_PROCESSING_INFORMATION", "DIGITAL_COMMUNICATION", "KNOWLEDGE", "SKILLS", "ATTITUDE", "BEHAVIOUR", "OTHER"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"DUTCH_READING", "DUTCH_WRITING", "MATH_NUMBERS", "MATH_PROPORTION", "MATH_GEOMETRY", "MATH_LINKS", "DIGITAL_USING_ICT_SYSTEMS", "DIGITAL_SEARCHING_INFORMATION", "DIGITAL_PROCESSING_INFORMATION", "DIGITAL_COMMUNICATION", "KNOWLEDGE", "SKILLS", "ATTITUDE", "BEHAVIOUR", "OTHER"},
     *             "example"="DUTCH_READING"
     *         }
     *     }
     * )
     */
    private string $topic;

    /**
     * @var string|null The topic of this LearningNeedOutcome when the OTHER option is selected.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="An other topic"
     *         }
     *     }
     * )
     */
    private ?string $topicOther;

    /**
     * @var string The application of this LearningNeedOutcome.
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @Assert\Choice({"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"},
     *             "example"="FAMILY_AND_PARENTING"
     *         }
     *     }
     * )
     */
    private string $application;

    /**
     * @var string|null The application of this LearningNeedOutcome when the OTHER option is selected.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="An other application"
     *         }
     *     }
     * )
     */
    private ?string $applicationOther;

    /**
     * @var string The level of this LearningNeedOutcome.
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"},
     *             "example"="INFLOW"
     *         }
     *     }
     * )
     */
    private string $level;

    /**
     * @var string|null The level of this LearningNeedOutcome when the OTHER option is selected.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="An other level"
     *         }
     *     }
     * )
     */
    private ?string $levelOther;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getGoal(): string
    {
        return $this->goal;
    }

    public function setGoal(string $goal): self
    {
        $this->goal = $goal;

        return $this;
    }

    public function getTopic(): string
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

    public function getApplication(): string
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

    public function getLevel(): string
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
}
