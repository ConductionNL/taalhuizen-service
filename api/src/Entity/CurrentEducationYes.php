<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CurrentEducationYesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=CurrentEducationYesRepository::class)
 */
class CurrentEducationYes
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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateSince;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $doesProvideCertificate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSince(): ?\DateTimeInterface
    {
        return $this->dateSince;
    }

    public function setDateSince(?\DateTimeInterface $dateSince): self
    {
        $this->dateSince = $dateSince;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDoesProvideCertificate(): ?bool
    {
        return $this->doesProvideCertificate;
    }

    public function setDoesProvideCertificate(?bool $doesProvideCertificate): self
    {
        $this->doesProvideCertificate = $doesProvideCertificate;

        return $this;
    }
}
