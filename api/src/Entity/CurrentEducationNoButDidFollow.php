<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CurrentEducationNoButDidFollowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=CurrentEducationNoButDidFollowRepository::class)
 */
class CurrentEducationNoButDidFollow
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
    private $dateUntil;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $level;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $gotCertificate;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }

    public function getDateUntil(): ?\DateTimeInterface
    {
        return $this->dateUntil;
    }

    public function setDateUntil(?\DateTimeInterface $dateUntil): self
    {
        $this->dateUntil = $dateUntil;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getGotCertificate(): ?bool
    {
        return $this->gotCertificate;
    }

    public function setGotCertificate(?bool $gotCertificate): self
    {
        $this->gotCertificate = $gotCertificate;

        return $this;
    }
}
