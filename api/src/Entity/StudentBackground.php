<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentBackgroundRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=StudentBackgroundRepository::class)
 */
class StudentBackground
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
    private $foundVia;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $foundViaOther;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $wentToTaalhuisBefore;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $wentToTaalhuisBeforeReason;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $wentToTaalhuisBeforeYear;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $network = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $participationLadder;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getFoundVia(): ?string
    {
        return $this->foundVia;
    }

    public function setFoundVia(?string $foundVia): self
    {
        $this->foundVia = $foundVia;

        return $this;
    }

    public function getFoundViaOther(): ?string
    {
        return $this->foundViaOther;
    }

    public function setFoundViaOther(?string $foundViaOther): self
    {
        $this->foundViaOther = $foundViaOther;

        return $this;
    }

    public function getWentToTaalhuisBefore(): ?bool
    {
        return $this->wentToTaalhuisBefore;
    }

    public function setWentToTaalhuisBefore(?bool $wentToTaalhuisBefore): self
    {
        $this->wentToTaalhuisBefore = $wentToTaalhuisBefore;

        return $this;
    }

    public function getWentToTaalhuisBeforeReason(): ?string
    {
        return $this->wentToTaalhuisBeforeReason;
    }

    public function setWentToTaalhuisBeforeReason(?string $wentToTaalhuisBeforeReason): self
    {
        $this->wentToTaalhuisBeforeReason = $wentToTaalhuisBeforeReason;

        return $this;
    }

    public function getWentToTaalhuisBeforeYear(): ?float
    {
        return $this->wentToTaalhuisBeforeYear;
    }

    public function setWentToTaalhuisBeforeYear(?float $wentToTaalhuisBeforeYear): self
    {
        $this->wentToTaalhuisBeforeYear = $wentToTaalhuisBeforeYear;

        return $this;
    }

    public function getNetwork(): ?array
    {
        return $this->network;
    }

    public function setNetwork(?array $network): self
    {
        $this->network = $network;

        return $this;
    }

    public function getParticipationLadder(): ?int
    {
        return $this->participationLadder;
    }

    public function setParticipationLadder(?int $participationLadder): self
    {
        $this->participationLadder = $participationLadder;

        return $this;
    }
}
