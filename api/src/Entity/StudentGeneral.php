<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentGeneralRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity StudentGeneral holds.
 *
 * This DTO is a subresource for the DTO Student. It contains the general details for a Student.
 * The main source that properties of this DTO entity are based on, is the following jira issue: https://lifely.atlassian.net/browse/BISC-76.
 *
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
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private UuidInterface $id;

    /**
     * @var String|null The country of origin of this student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $countryOfOrigin;

    /**
     * @var String|null The native language of this student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $nativeLanguage;

    /**
     * @var String|null The other languages this student speaks.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $otherLanguages;

    /**
     * @var array|null The family composition of this student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice(multiple=true, choices={"MARRIED_PARTNER", "SINGLE", "DIVORCED", "WIDOW"})
     * @ORM\Column(type="array", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="array",
     *             "items"={
     *               "type"="string",
     *               "enum"={"MARRIED_PARTNER", "SINGLE", "DIVORCED", "WIDOW"},
     *               "example"="MARRIED_PARTNER"
     *             }
     *         }
     *     }
     * )
     */
    private ?array $familyComposition = [];

    /**
     * @var int|null The amount of children of this student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $childrenCount;

    /**
     * @var String|null The birthdays of the children of this student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $childrenDatesOfBirth;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
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
