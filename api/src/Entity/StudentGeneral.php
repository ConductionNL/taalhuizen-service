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
 *     itemOperations={},
 *     collectionOperations={}
 * )
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
     * @var string|null The country of origin of this student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="The Netherlands"
     *         }
     *     }
     * )
     */
    private ?string $countryOfOrigin;

    /**
     * @var string|null The native language of this student.
     *
     * @Groups({"read", "write"})
     * @Assert\Length(min=2, max=3)
     * @ORM\Column(type="string", length=3, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="NL"
     *         }
     *     }
     * )
     */
    private ?string $nativeLanguage;

    /**
     * @var string|null The other languages this student speaks.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="English, Spanish"
     *         }
     *     }
     * )
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
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"=2
     *         }
     *     }
     * )
     */
    private ?int $childrenCount;

    /**
     * @var string|null The birthdays of the children of this student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="01-01-2006, 04-08-1999"
     *         }
     *     }
     * )
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
