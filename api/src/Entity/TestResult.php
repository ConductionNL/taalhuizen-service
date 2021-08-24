<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Repository\TestResultRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity TestResult holds.
 *
 * The main entity associated with this DTO is the edu/Result: https://taalhuizen-bisc.commonground.nu/api/v1/edu#tag/Result.
 * DTO TestResult exists of properties based on the following jira epics: https://lifely.atlassian.net/browse/BISC-64 and https://lifely.atlassian.net/browse/BISC-115.
 * And mainly the following issues: https://lifely.atlassian.net/browse/BISC-93 & https://lifely.atlassian.net/browse/BISC-140.
 * The learningNeedOutCome input fields are a recurring thing throughout multiple DTO entities, that is why the LearningNeedOutCome Entity was created and used here instead of matching the exact properties in the graphql schema.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get"={
 *              "read"=false,
 *              "validate"=false
 *          },
 *          "put"={
 *             "read"=false,
 *          },
 *          "delete"={
 *             "read"=false,
 *             "validate"=false
 *          },
 *     },
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
     * @var ?string The id of a participation this TestResult is connected to.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @Assert\Length(min=36, max=36)
     * @ORM\Column(type="string", length=36)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="e2984465-190a-4562-829e-a8cca81aa35d"
     *         }
     *     }
     * )
     */
    private ?string $participationId = null;

    /**
     * @var ?LearningNeedOutCome The learningNeedOutCome of this TestResult.
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @Assert\Valid
     * @MaxDepth(1)
     */
    private ?LearningNeedOutCome $learningNeedOutCome = null;

    /**
     * @var ?string The used exam for this TestResult.
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="Exam x about computers"
     *         }
     *     }
     * )
     */
    private ?string $usedExam = null;

    /**
     * @var ?DateTime The date of the exam that this TestResult is a result of.
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", length=255)
     */
    private ?DateTime $examDate  = null;

    /**
     * @var string|null A memo/note for this TestResult.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="This test was very hard!"
     *         }
     *     }
     * )
     */
    private ?string $memo = null;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getParticipationId(): ?string
    {
        return $this->participationId;
    }

    public function setParticipationId(?string $participationId): self
    {
        $this->participationId = $participationId;

        return $this;
    }

    public function getLearningNeedOutCome(): ?LearningNeedOutCome
    {
        return $this->learningNeedOutCome;
    }

    public function setLearningNeedOutCome(?LearningNeedOutCome $learningNeedOutCome): self
    {
        $this->learningNeedOutCome = $learningNeedOutCome;

        return $this;
    }

    public function getUsedExam(): ?string
    {
        return $this->usedExam;
    }

    public function setUsedExam(?string $usedExam): self
    {
        $this->usedExam = $usedExam;

        return $this;
    }

    public function getExamDate(): ?DateTime
    {
        return $this->examDate;
    }

    public function setExamDate(?DateTime $examDate): self
    {
        $this->examDate = $examDate;

        return $this;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): self
    {
        $this->memo = $memo;

        return $this;
    }
}
