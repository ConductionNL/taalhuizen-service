<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentJobRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
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
    private UuidInterface $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $trainedForJob;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastJob;

    /**
     * @Assert\Choice(multiple=true, choices={"SEARCHING_FOR_JOB", "RE_INTEGRATION", "SCHOOL", "VOLUNTEER_JOB", "JOB", "OTHER"})
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $dayTimeActivities = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $dayTimeActivitiesOther;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
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
