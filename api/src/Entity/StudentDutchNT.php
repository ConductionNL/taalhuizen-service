<?php

namespace App\Entity;

use App\Repository\StudentDutchNTRepository;
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
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dutchNTLevel;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $inNetherlandsSinceYear;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $languageInDailyLife;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $knowsLatinAlphabet;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastKnownLevel;

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
