<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentMotivationRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=StudentMotivationRepository::class)
 */
class StudentMotivation
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
     * @var array|null The desired skills for a StudentMotivation.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice(multiple=true, choices={
     *     "KLIKTIK", "USING_WHATSAPP", "USING_SKYPE", "DEVICE_FUNCTIONALITIES", "DIGITAL_GOVERNMENT", "RESERVE_BOOKS_IN_LIBRARY",
     *     "ADS_ON_MARKTPLAATS", "READ_FOR_CHILDREN", "UNDERSTAND_PRESCRIPTIONS", "WRITE_APPLICATION_LETTER", "WRITE_POSTCARD_FOR_FAMILY",
     *     "DO_ADMINISTRATION", "CALCULATIONS_FOR_RECIPES", "OTHER"
     * })
     * @ORM\Column(type="array", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="array",
     *             "items"={
     *               "type"="string",
     *               "enum"={
     *                  "KLIKTIK", "USING_WHATSAPP", "USING_SKYPE", "DEVICE_FUNCTIONALITIES", "DIGITAL_GOVERNMENT", "RESERVE_BOOKS_IN_LIBRARY",
     *                  "ADS_ON_MARKTPLAATS", "READ_FOR_CHILDREN", "UNDERSTAND_PRESCRIPTIONS", "WRITE_APPLICATION_LETTER", "WRITE_POSTCARD_FOR_FAMILY",
     *                  "DO_ADMINISTRATION", "CALCULATIONS_FOR_RECIPES", "OTHER"},
     *               "example"="USING_WHATSAPP"
     *             }
     *         }
     *     }
     * )
     */
    private ?array $desiredSkills = [];

    /**
     * @var String|null The desired skills for when the OTHER option is selected.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredSkillsOther;

    /**
     * @var bool|null A boolean that is true when the student has tried this before.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $hasTriedThisBefore;

    /**
     * @var String|null The explanation why the student has or has not tried this before.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $hasTriedThisBeforeExplanation;

    /**
     * @var String|null The reason why the student wants to learn these skills.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $whyWantTheseSkills;

    /**
     * @var String|null The reason why the student wants to learn these skills right now.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $whyWantThisNow;

    /**
     * @var array|null The desired learning methods for this StudentMotivation.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice(multiple=true, choices={"IN_A_GROUP", "ONE_ON_ONE", "HOME_ENVIRONMENT", "IN_LIBRARY_OR_OTHER", "ONLINE"})
     * @ORM\Column(type="array", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="array",
     *             "items"={
     *               "type"="string",
     *               "enum"={"IN_A_GROUP", "ONE_ON_ONE", "HOME_ENVIRONMENT", "IN_LIBRARY_OR_OTHER", "ONLINE"},
     *               "example"="IN_A_GROUP"
     *             }
     *         }
     *     }
     * )
     */
    private ?array $desiredLearningMethod = [];

    /**
     * @var String|null The final remark/note for the StudentMotivation.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $remarks;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getDesiredSkills(): ?array
    {
        return $this->desiredSkills;
    }

    public function setDesiredSkills(?array $desiredSkills): self
    {
        $this->desiredSkills = $desiredSkills;

        return $this;
    }

    public function getDesiredSkillsOther(): ?string
    {
        return $this->desiredSkillsOther;
    }

    public function setDesiredSkillsOther(?string $desiredSkillsOther): self
    {
        $this->desiredSkillsOther = $desiredSkillsOther;

        return $this;
    }

    public function getHasTriedThisBefore(): ?bool
    {
        return $this->hasTriedThisBefore;
    }

    public function setHasTriedThisBefore(?bool $hasTriedThisBefore): self
    {
        $this->hasTriedThisBefore = $hasTriedThisBefore;

        return $this;
    }

    public function getHasTriedThisBeforeExplanation(): ?string
    {
        return $this->hasTriedThisBeforeExplanation;
    }

    public function setHasTriedThisBeforeExplanation(?string $hasTriedThisBeforeExplanation): self
    {
        $this->hasTriedThisBeforeExplanation = $hasTriedThisBeforeExplanation;

        return $this;
    }

    public function getWhyWantTheseSkills(): ?string
    {
        return $this->whyWantTheseSkills;
    }

    public function setWhyWantTheseSkills(?string $whyWantTheseSkills): self
    {
        $this->whyWantTheseSkills = $whyWantTheseSkills;

        return $this;
    }

    public function getWhyWantThisNow(): ?string
    {
        return $this->whyWantThisNow;
    }

    public function setWhyWantThisNow(?string $whyWantThisNow): self
    {
        $this->whyWantThisNow = $whyWantThisNow;

        return $this;
    }

    public function getDesiredLearningMethod(): ?array
    {
        return $this->desiredLearningMethod;
    }

    public function setDesiredLearningMethod(?array $desiredLearningMethod): self
    {
        $this->desiredLearningMethod = $desiredLearningMethod;

        return $this;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(?string $remarks): self
    {
        $this->remarks = $remarks;

        return $this;
    }
}
