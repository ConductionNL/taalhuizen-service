<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AvailabilityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=AvailabilityRepository::class)
 */
class Availability
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
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $monday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $tuesday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $wednesday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $thursday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $friday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $saturday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $sunday;

    public function getId(): UuidInterface
    {
        return $this->id;
    }
    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }

    public function getMonday(): ?AvailabilityDay
    {
        return $this->monday;
    }

    public function setMonday(AvailabilityDay $monday): self
    {
        $this->monday = $monday;

        return $this;
    }

    public function getTuesday(): ?AvailabilityDay
    {
        return $this->tuesday;
    }

    public function setTuesday(AvailabilityDay $tuesday): self
    {
        $this->tuesday = $tuesday;

        return $this;
    }

    public function getWednesday(): ?AvailabilityDay
    {
        return $this->wednesday;
    }

    public function setWednesday(AvailabilityDay $wednesday): self
    {
        $this->wednesday = $wednesday;

        return $this;
    }

    public function getThursday(): ?AvailabilityDay
    {
        return $this->thursday;
    }

    public function setThursday(AvailabilityDay $thursday): self
    {
        $this->thursday = $thursday;

        return $this;
    }

    public function getFriday(): ?AvailabilityDay
    {
        return $this->friday;
    }

    public function setFriday(AvailabilityDay $friday): self
    {
        $this->friday = $friday;

        return $this;
    }

    public function getSaturday(): ?AvailabilityDay
    {
        return $this->saturday;
    }

    public function setSaturday(AvailabilityDay $saturday): self
    {
        $this->saturday = $saturday;

        return $this;
    }

    public function getSunday(): ?AvailabilityDay
    {
        return $this->sunday;
    }

    public function setSunday(AvailabilityDay $sunday): self
    {
        $this->sunday = $sunday;

        return $this;
    }
}
