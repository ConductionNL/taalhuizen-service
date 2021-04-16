<?php

namespace App\Entity;

use App\Repository\StudentMotivationRepository;
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
    private $id;

    /**
     * @ORM\Column(type="array")
     */
    private $desiredSkills = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $desiredSkillsOther;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hasTriedThisBefore;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $hasTriedThisBeforeExplanation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $whyWantTheseSkills;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $whyWantThisNow;

    /**
     * @ORM\Column(type="array")
     */
    private $desiredLearingMethod = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $remarks;

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

    public function setDesiredSkills(array $desiredSkills): self
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

    public function setDesiredLearingMethod(array $desiredLearingMethod): self
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
