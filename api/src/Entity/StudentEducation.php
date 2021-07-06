<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentEducationRepository;
use DateTime;
use DateTimeInterface;
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
 * @ORM\Entity(repositoryClass=StudentEducationRepository::class)
 */
class StudentEducation
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
     * @Assert\Choice({"NO_EDUCATION", "SOME_YEARS_PO", "PO", "VO", "MBO", "HBO", "UNIVERSITY"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastFollowedEducation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $didGraduate;

    /**
     * @Assert\Choice({"YES", "NO", "NO_BUT_DID_EARLIER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $followingEducationRightNow;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $followingEducationRightNowYesStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $followingEducationRightNowYesEndDate;

    /**
     * @Assert\Choice({"LANGUAGE_COURSE", "BO", "HBO", "WO", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $followingEducationRightNowYesLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $followingEducationRightNowYesInstitute;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $followingEducationRightNowYesProvidesCertificate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $followingEducationRightNowNoEndDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $followingEducationRightNowNoLevel;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $followingEducationRightNowNoGotCertificate;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
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
