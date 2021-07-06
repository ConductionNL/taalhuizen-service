<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentCourseRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isFollowingCourseRightNow;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $courseName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $courseTeacher;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $courseGroup;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $amountOfHours;

    /**
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
