<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentCourseRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
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
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
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
     * @var String|null The name of the course this student is following.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $courseName;

    /**
     * @var String|null The type of teacher this student has for his course.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"PROFESSIONAL", "VOLUNTEER", "BOTH"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $courseTeacher;

    /**
     * @var String|null The group type, Individually or Group, of the course this student is following.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"INDIVIDUALLY", "GROUP"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $courseGroup;

    /**
     * @var int|null The amount of hours the course takes, that this student is following.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $amountOfHours;

    /**
     * @var bool|null A boolean that is true if the course this student is following provides a certificate when completed.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $doesCourseProvideCertificate;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
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

    public function getCourseName(): ?string
    {
        return $this->courseName;
    }

    public function setCourseName(?string $courseName): self
    {
        $this->courseName = $courseName;

        return $this;
    }

    public function getCourseTeacher(): ?string
    {
        return $this->courseTeacher;
    }

    public function setCourseTeacher(?string $courseTeacher): self
    {
        $this->courseTeacher = $courseTeacher;

        return $this;
    }

    public function getCourseGroup(): ?string
    {
        return $this->courseGroup;
    }

    public function setCourseGroup(?string $courseGroup): self
    {
        $this->courseGroup = $courseGroup;

        return $this;
    }

    public function getAmountOfHours(): ?int
    {
        return $this->amountOfHours;
    }

    public function setAmountOfHours(?int $amountOfHours): self
    {
        $this->amountOfHours = $amountOfHours;

        return $this;
    }

    public function getDoesCourseProvideCertificate(): ?bool
    {
        return $this->doesCourseProvideCertificate;
    }

    public function setDoesCourseProvideCertificate(?bool $doesCourseProvideCertificate): self
    {
        $this->doesCourseProvideCertificate = $doesCourseProvideCertificate;

        return $this;
    }
}
