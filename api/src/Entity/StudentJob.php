<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentJobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;


/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=StudentJobRepository::class)
 */
class StudentJob
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
    private $trainedForJob;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastJob;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $dayTimeActivities = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dayTimeActivitiesOther;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrainedForJob(): ?string
    {
        return $this->trainedForJob;
    }

    public function setTrainedForJob(?string $trainedForJob): self
    {
        $this->trainedForJob = $trainedForJob;

        return $this;
    }

    public function getLastJob(): ?string
    {
        return $this->lastJob;
    }

    public function setLastJob(?string $lastJob): self
    {
        $this->lastJob = $lastJob;

        return $this;
    }

    public function getDayTimeActivities(): ?array
    {
        return $this->dayTimeActivities;
    }

    public function setDayTimeActivities(?array $dayTimeActivities): self
    {
        $this->dayTimeActivities = $dayTimeActivities;

        return $this;
    }

    public function getDayTimeActivitiesOther(): ?string
    {
        return $this->dayTimeActivitiesOther;
    }

    public function setDayTimeActivitiesOther(?string $dayTimeActivitiesOther): self
    {
        $this->dayTimeActivitiesOther = $dayTimeActivitiesOther;

        return $this;
    }
}
