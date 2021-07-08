<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentEducationRepository;
use DateTime;
use DateTimeInterface;
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
 * @ORM\Entity(repositoryClass=StudentEducationRepository::class)
 */
class StudentEducation
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
     * @var String|null The last followed education of this StudentEducation.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"NO_EDUCATION", "SOME_YEARS_PO", "PO", "VO", "MBO", "HBO", "UNIVERSITY"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"NO_EDUCATION", "SOME_YEARS_PO", "PO", "VO", "MBO", "HBO", "UNIVERSITY"},
     *             "example"="NO_EDUCATION"
     *         }
     *     }
     * )
     */
    private ?string $lastFollowedEducation;

    /**
     * @var bool|null A boolean that is true when the student graduated for his/her last followed education.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $didGraduate;

    /**
     * @var String|null A enum for if the student is following an education right now or not.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"YES", "NO", "NO_BUT_DID_EARLIER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"YES", "NO", "NO_BUT_DID_EARLIER"},
     *             "example"="YES"
     *         }
     *     }
     * )
     */
    private ?string $followingEducationRightNow;

    /**
     * @var DateTimeInterface|null If the student is following an education right now this is used for the start date of that education.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $followingEducationRightNowYesStartDate;

    /**
     * @var DateTimeInterface|null If the student is following an education right now this is used for the end date of that education.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $followingEducationRightNowYesEndDate;

    /**
     * @var String|null If the student is following an education right now this is used for the level of that education.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"LANGUAGE_COURSE", "BO", "HBO", "WO", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"LANGUAGE_COURSE", "BO", "HBO", "WO", "OTHER"},
     *             "example"="LANGUAGE_COURSE"
     *         }
     *     }
     * )
     */
    private ?string $followingEducationRightNowYesLevel;

    /**
     * @var String|null If the student is following an education right now this is used for the institute of that education.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $followingEducationRightNowYesInstitute;

    /**
     * @var bool|null If the student is following an education right now this is true if that education provides a certificate.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $followingEducationRightNowYesProvidesCertificate;

    /**
     * @var DateTimeInterface|null If the student is not following an education right now this can be used for the end date of the last education.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $followingEducationRightNowNoEndDate;

    /**
     * @var String|null If the student is not following an education right now this is used for the level of the last education.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $followingEducationRightNowNoLevel;

    /**
     * @var bool|null If the student is not following an education right now this is true if the last education provides a certificate.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $followingEducationRightNowNoGotCertificate;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getLastFollowedEducation(): ?string
    {
        return $this->lastFollowedEducation;
    }

    public function setLastFollowedEducation(?string $lastFollowedEducation): self
    {
        $this->lastFollowedEducation = $lastFollowedEducation;

        return $this;
    }

    public function getDidGraduate(): ?bool
    {
        return $this->didGraduate;
    }

    public function setDidGraduate(?bool $didGraduate): self
    {
        $this->didGraduate = $didGraduate;

        return $this;
    }

    public function getFollowingEducationRightNow(): ?string
    {
        return $this->followingEducationRightNow;
    }

    public function setFollowingEducationRightNow(?string $followingEducationRightNow): self
    {
        $this->followingEducationRightNow = $followingEducationRightNow;

        return $this;
    }

    public function getFollowingEducationRightNowYesStartDate(): ?DateTimeInterface
    {
        return $this->followingEducationRightNowYesStartDate;
    }

    public function setFollowingEducationRightNowYesStartDate(?DateTimeInterface $followingEducationRightNowYesStartDate): self
    {
        $this->followingEducationRightNowYesStartDate = $followingEducationRightNowYesStartDate;

        return $this;
    }

    public function getFollowingEducationRightNowYesEndDate(): ?DateTimeInterface
    {
        return $this->followingEducationRightNowYesEndDate;
    }

    public function setFollowingEducationRightNowYesEndDate(?DateTimeInterface $followingEducationRightNowYesEndDate): self
    {
        $this->followingEducationRightNowYesEndDate = $followingEducationRightNowYesEndDate;

        return $this;
    }

    public function getFollowingEducationRightNowYesLevel(): ?string
    {
        return $this->followingEducationRightNowYesLevel;
    }

    public function setFollowingEducationRightNowYesLevel(?string $followingEducationRightNowYesLevel): self
    {
        $this->followingEducationRightNowYesLevel = $followingEducationRightNowYesLevel;

        return $this;
    }

    public function getFollowingEducationRightNowYesInstitute(): ?string
    {
        return $this->followingEducationRightNowYesInstitute;
    }

    public function setFollowingEducationRightNowYesInstitute(?string $followingEducationRightNowYesInstitute): self
    {
        $this->followingEducationRightNowYesInstitute = $followingEducationRightNowYesInstitute;

        return $this;
    }

    public function getFollowingEducationRightNowYesProvidesCertificate(): ?bool
    {
        return $this->followingEducationRightNowYesProvidesCertificate;
    }

    public function setFollowingEducationRightNowYesProvidesCertificate(?bool $followingEducationRightNowYesProvidesCertificate): self
    {
        $this->followingEducationRightNowYesProvidesCertificate = $followingEducationRightNowYesProvidesCertificate;

        return $this;
    }

    public function getFollowingEducationRightNowNoEndDate(): ?DateTimeInterface
    {
        return $this->followingEducationRightNowNoEndDate;
    }

    public function setFollowingEducationRightNowNoEndDate(?DateTimeInterface $followingEducationRightNowNoEndDate): self
    {
        $this->followingEducationRightNowNoEndDate = $followingEducationRightNowNoEndDate;

        return $this;
    }

    public function getFollowingEducationRightNowNoLevel(): ?string
    {
        return $this->followingEducationRightNowNoLevel;
    }

    public function setFollowingEducationRightNowNoLevel(?string $followingEducationRightNowNoLevel): self
    {
        $this->followingEducationRightNowNoLevel = $followingEducationRightNowNoLevel;

        return $this;
    }

    public function getFollowingEducationRightNowNoGotCertificate(): ?bool
    {
        return $this->followingEducationRightNowNoGotCertificate;
    }

    public function setFollowingEducationRightNowNoGotCertificate(?bool $followingEducationRightNowNoGotCertificate): self
    {
        $this->followingEducationRightNowNoGotCertificate = $followingEducationRightNowNoGotCertificate;

        return $this;
    }
}
