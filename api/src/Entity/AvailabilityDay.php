<?php

namespace App\Entity;

use App\Repository\AvailabilityDayRepository;
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
 * All properties that the DTO entity AvailabilityDay holds.
 *
 * This DTO is a subresource for the DTO Availability. It contains the availability of a specific day for an Availability.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     }
 * )
 * @ORM\Entity(repositoryClass=AvailabilityDayRepository::class)
 */
class AvailabilityDay
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
     * @var bool Morning of this availability day.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private bool $morning;

    /**
     * @var bool Afternoon of this availability day.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private bool $afternoon;

    /**
     * @var bool Evening of this availability day.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
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

    public function getMorning(): ?bool
    {
        return $this->morning;
    }

    public function setMorning(bool $morning): self
    {
        $this->morning = $morning;

        return $this;
    }

    public function getAfternoon(): ?bool
    {
        return $this->afternoon;
    }

    public function setAfternoon(bool $afternoon): self
    {
        $this->afternoon = $afternoon;

        return $this;
    }

    public function getEvening(): ?bool
    {
        return $this->evening;
    }

    public function setEvening(bool $evening): self
    {
        $this->evening = $evening;

        return $this;
    }
}
