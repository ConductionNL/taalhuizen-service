<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;
use App\Repository\StudentCourseRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity StudentCourse holds.
 *
 * This DTO is a subresource for the DTO Student. It contains the course details for a Student.
 * The main source that properties of this DTO entity are based on, is the following jira issue: https://lifely.atlassian.net/browse/BISC-76.
 * The course input fields match the Education Entity, that is why there is an Education object used here instead of matching the exact properties in the graphql schema.
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
 * @ORM\Entity(repositoryClass=StudentCourseRepository::class)
 */
class StudentCourse
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
     * @var bool|null A boolean that is true if this student is following a course right now.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isFollowingCourseRightNow;

    /**
     * @var ?Education The course Education of this studentCourse.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Education::class, cascade={"persist", "remove"})
     */
    private ?Education $course;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getIsFollowingCourseRightNow(): ?bool
    {
        return $this->isFollowingCourseRightNow;
    }

    public function setIsFollowingCourseRightNow(?bool $isFollowingCourseRightNow): self
    {
        $this->isFollowingCourseRightNow = $isFollowingCourseRightNow;

        return $this;
    }

    public function getCourse(): ?Education
    {
        return $this->course;
    }

    public function setCourse(?Education $course): self
    {
        $this->course = $course;

        return $this;
    }
}
