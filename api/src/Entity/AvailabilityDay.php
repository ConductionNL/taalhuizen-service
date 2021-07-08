<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AvailabilityDayRepository;
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
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $morning;

    /**
     * @ORM\Column(type="boolean")
     */
    private $afternoon;

    /**
     * @ORM\Column(type="boolean")
     */
    private $evening;

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
