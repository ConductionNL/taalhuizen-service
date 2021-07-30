<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\EducationRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity Education holds.
 *
 * The main entity associated with this DTO is the mrc/Education: https://taalhuizen-bisc.commonground.nu/api/v1/mrc#tag/Education.
 * DTO Education exists of properties based on this medewerker catalogue entity, that is based on a https://www.hropenstandards.org/ schema.
 * The Education input is a recurring thing throughout multiple DTO entities like: StudentEducation, StudentCourse and Employee.
 * Notable is that a few properties are renamed here, compared to the graphql schema, this was done for consistency and cleaner names, but mostly to match the mrc/Education Entity.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={"get"},
 *     collectionOperations={"get"}
 * )
 * @ORM\Entity(repositoryClass=EducationRepository::class)
 */
class Education
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
     * @var string|null The name of the course this student is following.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Language course"
     *         }
     *     }
     * )
     */
    private ?string $name;

    /**
     * @var ?DateTime Start date of this education.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="DateTime",
     *             "example"="12-07-2021"
     *         }
     *     }
     * )
     */
    private ?DateTime $startDate;

    /**
     * @var ?DateTime End date of this education.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="DateTime",
     *             "example"="12-10-2021"
     *         }
     *     }
     * )
     */
    private ?DateTime $endDate;

    /**
     * @var ?string Institution of this education.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Institution X"
     *         }
     *     }
     * )
     */
    private ?string $institution;

    /**
     * @var ?string Isced education level code of this education.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="HBO"
     *         }
     *     }
     * )
     */
    private ?string $iscedEducationLevelCode;

    /**
     * @var ?string Degree granted status of this education.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Granted"
     *         }
     *     }
     * )
     */
    private ?string $degreeGrantedStatus;

    /**
     * @var string|null The group formation type of this (course) Education.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"INDIVIDUALLY", "GROUP"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"INDIVIDUALLY", "GROUP"},
     *             "example"="INDIVIDUALLY"
     *         }
     *     }
     * )
     */
    private ?string $groupFormation;

    /**
     * @var string|null The professionalism of the teacher for this Education.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"PROFESSIONAL", "VOLUNTEER", "BOTH"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"PROFESSIONAL", "VOLUNTEER", "BOTH"},
     *             "example"="PROFESSIONAL"
     *         }
     *     }
     * )
     */
    private ?string $teacherProfessionalism;

    /**
     * @var string|null The professionalism of this Education if this education is a course.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"PROFESSIONAL", "VOLUNTEER", "BOTH"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"PROFESSIONAL", "VOLUNTEER", "BOTH"},
     *             "example"="PROFESSIONAL"
     *         }
     *     }
     * )
     */
    private ?string $courseProfessionalism;

    /**
     * @var bool|null A boolean that is true if the Education provides a certificate when completed.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="bool",
     *             "example"=true
     *         }
     *     }
     * )
     */
    private ?bool $providesCertificate;

    /**
     * @var int|null The amount of hours the course takes, that this student is following.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="int",
     *             "example"="25"
     *         }
     *     }
     * )
     */
    private ?int $amountOfHours;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEnddate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEnddate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getInstitution(): ?string
    {
        return $this->institution;
    }

    public function setInstitution(?string $institution): self
    {
        $this->institution = $institution;

        return $this;
    }

    public function getDegreeGrantedStatus(): ?string
    {
        return $this->degreeGrantedStatus;
    }

    public function setDegreeGrantedStatus(?string $degreeGrantedStatus): self
    {
        $this->degreeGrantedStatus = $degreeGrantedStatus;

        return $this;
    }

    public function getIscedEducationLevelCode(): ?string
    {
        return $this->iscedEducationLevelCode;
    }

    public function setIscedEducationLevelCode(?string $iscedEducationLevelCode): self
    {
        $this->iscedEducationLevelCode = $iscedEducationLevelCode;

        return $this;
    }

    public function getGroupFormation(): ?string
    {
        return $this->groupFormation;
    }

    public function setGroupFormation(?string $groupFormation): self
    {
        $this->groupFormation = $groupFormation;

        return $this;
    }

    public function getTeacherProfessionalism(): ?string
    {
        return $this->teacherProfessionalism;
    }

    public function setTeacherProfessionalism(?string $teacherProfessionalism): self
    {
        $this->teacherProfessionalism = $teacherProfessionalism;

        return $this;
    }

    public function getCourseProfessionalism(): ?string
    {
        return $this->courseProfessionalism;
    }

    public function setCourseProfessionalism(?string $courseProfessionalism): self
    {
        $this->courseProfessionalism = $courseProfessionalism;

        return $this;
    }

    public function getProvidesCertificate(): ?bool
    {
        return $this->providesCertificate;
    }

    public function setProvidesCertificate(?bool $providesCertificate): self
    {
        $this->providesCertificate = $providesCertificate;

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
}
