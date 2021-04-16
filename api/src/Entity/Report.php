<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=ReportRepository::class)
 */
class Report
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
    private $taalhuisId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederId;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateFrom;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dateUntil;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }

    public function getTaalhuisId(): ?string
    {
        return $this->taalhuisId;
    }

    public function setTaalhuisId(?string $taalhuisId): self
    {
        $this->taalhuisId = $taalhuisId;

        return $this;
    }

    public function getAanbiederId(): ?string
    {
        return $this->aanbiederId;
    }

    public function setAanbiederId(?string $aanbiederId): self
    {
        $this->aanbiederId = $aanbiederId;

        return $this;
    }

    public function getDateFrom(): ?\DateTimeInterface
    {
        return $this->dateFrom;
    }

    public function setDateFrom(?\DateTimeInterface $dateFrom): self
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function getDateUntil(): ?string
    {
        return $this->dateUntil;
    }

    public function setDateUntil(?string $dateUntil): self
    {
        $this->dateUntil = $dateUntil;

        return $this;
    }
}
