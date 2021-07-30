<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AvailabilityDayRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity AvailabilityDay holds.
 *
 * This DTO is a subresource for the DTO Availability. It contains the availability of a specific day for an Availability.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={"get"},
 *     collectionOperations={"get"}
 * )
 * @ORM\Entity(repositoryClass=AvailabilityDayRepository::class)
 */
class AvailabilityDay
{
    /**
     * @var UuidInterface The UUID identifier of this resource
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private UuidInterface $id;

    /**
     * @var bool Morning of this availability day (6:00 -> 12:00).
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="bool",
     *             "example"=true
     *         }
     *     }
     * )
     */
    private bool $morning;

    /**
     * @var bool Afternoon of this availability day (12:00 -> 18:00).
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="bool",
     *             "example"=true
     *         }
     *     }
     * )
     */
    private bool $afternoon;

    /**
     * @var bool Evening of this availability day (18:00 -> 00:00).
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="bool",
     *             "example"=true
     *         }
     *     }
     * )
     */
    private bool $evening;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getMorning(): bool
    {
        return $this->morning;
    }

    public function setMorning(bool $morning): self
    {
        $this->morning = $morning;

        return $this;
    }

    public function getAfternoon(): bool
    {
        return $this->afternoon;
    }

    public function setAfternoon(bool $afternoon): self
    {
        $this->afternoon = $afternoon;

        return $this;
    }

    public function getEvening(): bool
    {
        return $this->evening;
    }

    public function setEvening(bool $evening): self
    {
        $this->evening = $evening;

        return $this;
    }
}
