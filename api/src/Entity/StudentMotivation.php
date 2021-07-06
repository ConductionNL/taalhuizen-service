<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentMotivationRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
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
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private UuidInterface $id;

    /**
     * @Assert\Choice(multiple=true, choices={
     *     "KLIKTIK", "USING_WHATSAPP", "USING_SKYPE", "DEVICE_FUNCTIONALITIES", "DIGITAL_GOVERNMENT", "RESERVE_BOOKS_IN_LIBRARY",
     *     "ADS_ON_MARKTPLAATS", "READ_FOR_CHILDREN", "UNDERSTAND_PRESCRIPTIONS", "WRITE_APPLICATION_LETTER", "WRITE_POSTCARD_FOR_FAMILY",
     *     "DO_ADMINISTRATION", "CALCULATIONS_FOR_RECIPES", "OTHER"
     * })
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $desiredSkills = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredSkillsOther;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $hasTriedThisBefore;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $hasTriedThisBeforeExplanation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $whyWantTheseSkills;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $whyWantThisNow;

    /**
     * @Assert\Choice(multiple=true, choices={"IN_A_GROUP", "ONE_ON_ONE", "HOME_ENVIRONMENT", "IN_LIBRARY_OR_OTHER", "ONLINE"})
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $desiredLearingMethod = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $remarks;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
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

    public function getDesiredLearingMethod(): ?array
    {
        return $this->desiredLearingMethod;
    }

    public function setDesiredLearingMethod(?array $desiredLearingMethod): self
    {
        $this->desiredLearingMethod = $desiredLearingMethod;

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
