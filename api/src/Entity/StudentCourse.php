<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
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
     * @var ?Education Education of this studentCourse.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Education::class, cascade={"persist", "remove"})
     */
    private ?Education $education;

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

    public function getEducation(): ?Education
    {
        return $this->education;
    }

    public function setEducation(?Education $education): self
    {
        $this->education = $education;

        return $this;
    }
}
