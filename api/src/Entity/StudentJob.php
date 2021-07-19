<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentJobRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity StudentJob holds.
 *
 * This DTO is a subresource for the DTO Student. It contains the job details for a Student.
 * The main source that properties of this DTO entity are based on, is the following jira issue: https://lifely.atlassian.net/browse/BISC-76.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "put",
 *          "delete"
 *     },
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
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private UuidInterface $id;

    /**
     * @var String|null The job this student is trained for.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $trainedForJob;

    /**
     * @var String|null The last job of this student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastJob;

    /**
     * @var array|null The daytime activities of this StudentJob.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice(multiple=true, choices={"SEARCHING_FOR_JOB", "RE_INTEGRATION", "SCHOOL", "VOLUNTEER_JOB", "JOB", "OTHER"})
     * @ORM\Column(type="array", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="array",
     *             "items"={
     *               "type"="string",
     *               "enum"={"SEARCHING_FOR_JOB", "RE_INTEGRATION", "SCHOOL", "VOLUNTEER_JOB", "JOB", "OTHER"},
     *               "example"="SEARCHING_FOR_JOB"
     *             }
     *         }
     *     }
     * )
     */
    private ?array $dayTimeActivities = [];

    /**
     * @var String|null The daytime activities for when the OTHER option is selected.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $dayTimeActivitiesOther;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
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
