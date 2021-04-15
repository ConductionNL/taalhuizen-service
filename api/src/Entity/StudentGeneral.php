<?php

namespace App\Entity;

use App\Repository\StudentGeneralRepository;
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
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $countryOfOrigin;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nativeLanguage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $otherLanguages;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $familiComposition = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $childrenCount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $childrenDatesOfBirth;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFamiliComposition(): ?array
    {
        return $this->familiComposition;
    }

    public function setFamiliComposition(?array $familiComposition): self
    {
        $this->familiComposition = $familiComposition;

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
