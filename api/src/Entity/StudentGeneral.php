<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentGeneralRepository;
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
 * @ORM\Entity(repositoryClass=StudentGeneralRepository::class)
 */
class StudentGeneral
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $countryOfOrigin;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $nativeLanguage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $otherLanguages;

    /**
     * @Assert\Choice(multiple=true, choices={"MARRIED_PARTNER", "SINGLE", "DIVORCED", "WIDOW"})
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $familyComposition = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $childrenCount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $childrenDatesOfBirth;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getCountryOfOrigin(): ?string
    {
        return $this->countryOfOrigin;
    }

    public function setCountryOfOrigin(?string $countryOfOrigin): self
    {
        $this->countryOfOrigin = $countryOfOrigin;

        return $this;
    }

    public function getNativeLanguage(): ?string
    {
        return $this->nativeLanguage;
    }

    public function setNativeLanguage(?string $nativeLanguage): self
    {
        $this->nativeLanguage = $nativeLanguage;

        return $this;
    }

    public function getOtherLanguages(): ?string
    {
        return $this->otherLanguages;
    }

    public function setOtherLanguages(?string $otherLanguages): self
    {
        $this->otherLanguages = $otherLanguages;

        return $this;
    }

    public function getFamilyComposition(): ?array
    {
        return $this->familyComposition;
    }

    public function setFamilyComposition(?array $familyComposition): self
    {
        $this->familyComposition = $familyComposition;

        return $this;
    }

    public function getChildrenCount(): ?int
    {
        return $this->childrenCount;
    }

    public function setChildrenCount(?int $childrenCount): self
    {
        $this->childrenCount = $childrenCount;

        return $this;
    }

    public function getChildrenDatesOfBirth(): ?string
    {
        return $this->childrenDatesOfBirth;
    }

    public function setChildrenDatesOfBirth(?string $childrenDatesOfBirth): self
    {
        $this->childrenDatesOfBirth = $childrenDatesOfBirth;

        return $this;
    }
}
