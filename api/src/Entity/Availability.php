<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=AvailabilityRepository::class)
 */
class Availability
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
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AvailabilityDay $monday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AvailabilityDay $tuesday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AvailabilityDay $wednesday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AvailabilityDay $thursday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AvailabilityDay $friday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AvailabilityDay $saturday;

    /**
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AvailabilityDay $sunday;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
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
