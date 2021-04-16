<?php

namespace App\Entity;

use App\Repository\StudentCourseRepository;
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
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isFollowingCourseRightNow;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $courseName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $courseTeacher;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $courseGroup;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $amountOfHours;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $doesCourseProvideCertificate;

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
