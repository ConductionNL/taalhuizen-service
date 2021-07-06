<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentDutchNTRepository;
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
 * @ORM\Entity(repositoryClass=StudentDutchNTRepository::class)
 */
class StudentDutchNT
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
     * @Assert\Choice({"NT1", "NT2"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $dutchNTLevel;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $inNetherlandsSinceYear;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $languageInDailyLife;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $knowsLatinAlphabet;

    /**
     * @Assert\Choice({"A0", "A1", "A2", "B1", "B2", "C1", "C2", "UNKNOWN"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastKnownLevel;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
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
