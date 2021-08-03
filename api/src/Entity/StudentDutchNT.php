<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentDutchNTRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity StudentDutchNT holds.
 *
 * This DTO is a subresource for the DTO Student. It contains the dutch NT details for a Student.
 * The main source that properties of this DTO entity are based on, is the following jira issue: https://lifely.atlassian.net/browse/BISC-76.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={"get"},
 *     collectionOperations={"get"}
 * )
 * @ORM\Entity(repositoryClass=StudentDutchNTRepository::class)
 */
class StudentDutchNT
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
     * @var string|null The dutch NT level of this Student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"NT1", "NT2"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"NT1", "NT2"},
     *             "example"="NT1"
     *         }
     *     }
     * )
     */
    private ?string $dutchNTLevel;

    /**
     * @var float|null The year since when this student is in the Netherlands.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="float", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"=2003
     *         }
     *     }
     * )
     */
    private ?float $inNetherlandsSinceYear;

    /**
     * @var string|null The language this student speaks in his/her daily life.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="Dutch"
     *         }
     *     }
     * )
     */
    private ?string $languageInDailyLife;

    /**
     * @var bool|null A boolean that is true if this student knows the latin alphabet.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $knowsLatinAlphabet;

    /**
     * @var string|null The last known language level of this student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"A0", "A1", "A2", "B1", "B2", "C1", "C2", "UNKNOWN"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"A0", "A1", "A2", "B1", "B2", "C1", "C2", "UNKNOWN"},
     *             "example"="A0"
     *         }
     *     }
     * )
     */
    private ?string $lastKnownLevel;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getDutchNTLevel(): ?string
    {
        return $this->dutchNTLevel;
    }

    public function setDutchNTLevel(?string $dutchNTLevel): self
    {
        $this->dutchNTLevel = $dutchNTLevel;

        return $this;
    }

    public function getInNetherlandsSinceYear(): ?float
    {
        return $this->inNetherlandsSinceYear;
    }

    public function setInNetherlandsSinceYear(?float $inNetherlandsSinceYear): self
    {
        $this->inNetherlandsSinceYear = $inNetherlandsSinceYear;

        return $this;
    }

    public function getLanguageInDailyLife(): ?string
    {
        return $this->languageInDailyLife;
    }

    public function setLanguageInDailyLife(?string $languageInDailyLife): self
    {
        $this->languageInDailyLife = $languageInDailyLife;

        return $this;
    }

    public function getKnowsLatinAlphabet(): ?bool
    {
        return $this->knowsLatinAlphabet;
    }

    public function setKnowsLatinAlphabet(?bool $knowsLatinAlphabet): self
    {
        $this->knowsLatinAlphabet = $knowsLatinAlphabet;

        return $this;
    }

    public function getLastKnownLevel(): ?string
    {
        return $this->lastKnownLevel;
    }

    public function setLastKnownLevel(?string $lastKnownLevel): self
    {
        $this->lastKnownLevel = $lastKnownLevel;

        return $this;
    }
}
