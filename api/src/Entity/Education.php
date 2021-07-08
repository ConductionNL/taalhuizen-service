<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use App\Repository\EducationRepository;
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
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     }
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
     * @var String|null The name of the course this student is following.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $name;

//   startDate of the education, was called in the graphql-schema 'dateSince', changed to 'startDate' related to schema.org
    /**
     * @var ?DateTime Start date of this education.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $startDate;

//   endDate of the education, was called in the graphql-schema 'dateUntil', changed to 'endDate' related to schema.org
    /**
     * @var ?DateTime End date of this education.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $endDate;

//   institution of the education, was called in the graphql-schema 'name', changed to 'institution' related to schema.org
    /**
     * @var ?string Institution of this education.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $institution;

//   isced education level code of the education, was called in the graphql-schema 'level', changed to 'iscedEducationLevelCode' related to schema.org
    /**
     * @var ?string Isced education level code of this education.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
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
     */
    private ?string $degreeGrantedStatus;

    /**
     * @var String|null The group formation type of this (course) Education.
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
     * @var String|null The professionalism of the teacher for this Education.
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
     * @var bool|null A boolean that is true if the Education provides a certificate when completed.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $provideCertificate;

    /**
     * @var int|null The amount of hours the course takes, that this student is following.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", nullable=true)
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

    public function getProvideCertificate(): ?bool
    {
        return $this->provideCertificate;
    }

    public function setProvideCertificate(?bool $provideCertificate): self
    {
        $this->provideCertificate = $provideCertificate;

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
