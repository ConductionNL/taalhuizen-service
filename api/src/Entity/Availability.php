<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Repository\AvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity Availability holds.
 *
 * The main entity associated with this DTO is the arc/Calendar: https://taalhuizen-bisc.commonground.nu/api/v1/arc#tag/Calendar. Containing arc/FreeBusy objects (https://taalhuizen-bisc.commonground.nu/api/v1/arc#tag/Freebusy).
 * (Note that this entity was not yet used in the old graphql version of the taalhuizen-service, back then this was just stored as an array with the cc/Person object).
 * The Availability input is a recurring thing throughout multiple DTO entities like: StudentAvailability, Employee and Group.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={"get"},
 *     collectionOperations={"get"}
 * )
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
     * @var AvailabilityDay Monday of this availability
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private AvailabilityDay $monday;

    /**
     * @var AvailabilityDay Tuesday of this availability
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private AvailabilityDay $tuesday;

    /**
     * @var AvailabilityDay Wednesday of this availability
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private AvailabilityDay $wednesday;

    /**
     * @var AvailabilityDay Thursday of this availability
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private AvailabilityDay $thursday;

    /**
     * @var AvailabilityDay Friday of this availability
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private AvailabilityDay $friday;

    /**
     * @var AvailabilityDay Saturday of this availability
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private AvailabilityDay $saturday;

    /**
     * @var AvailabilityDay Sunday of this availability
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=AvailabilityDay::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private AvailabilityDay $sunday;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getMonday(): AvailabilityDay
    {
        return $this->monday;
    }

    public function setMonday(AvailabilityDay $monday): self
    {
        $this->monday = $monday;

        return $this;
    }

    public function getTuesday(): AvailabilityDay
    {
        return $this->tuesday;
    }

    public function setTuesday(AvailabilityDay $tuesday): self
    {
        $this->tuesday = $tuesday;

        return $this;
    }

    public function getWednesday(): AvailabilityDay
    {
        return $this->wednesday;
    }

    public function setWednesday(AvailabilityDay $wednesday): self
    {
        $this->wednesday = $wednesday;

        return $this;
    }

    public function getThursday(): AvailabilityDay
    {
        return $this->thursday;
    }

    public function setThursday(AvailabilityDay $thursday): self
    {
        $this->thursday = $thursday;

        return $this;
    }

    public function getFriday(): AvailabilityDay
    {
        return $this->friday;
    }

    public function setFriday(AvailabilityDay $friday): self
    {
        $this->friday = $friday;

        return $this;
    }

    public function getSaturday(): AvailabilityDay
    {
        return $this->saturday;
    }

    public function setSaturday(AvailabilityDay $saturday): self
    {
        $this->saturday = $saturday;

        return $this;
    }

    public function getSunday(): AvailabilityDay
    {
        return $this->sunday;
    }

    public function setSunday(AvailabilityDay $sunday): self
    {
        $this->sunday = $sunday;

        return $this;
    }
}
