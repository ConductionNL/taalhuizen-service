<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentEducationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;


/**
 * @ApiResource()
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
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastFollowedEducation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $didGraduate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $followingEducationRightNow;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $followingEducationRightNowYesStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $followingEducationRightNowYesEndDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $followingEducationRightNowYesLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $followingEducationRightNowYesInstitute;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $followingEducationRightNowYesProvidesCertificate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $followingEducationRightNowNoEndDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $followingEducationRightNowNoLevel;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $followingEducationRightNowNoGotCertificate;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFollowingEducationRightNowYesStartDate(): ?\DateTimeInterface
    {
        return $this->followingEducationRightNowYesStartDate;
    }

    public function setFollowingEducationRightNowYesStartDate(?\DateTimeInterface $followingEducationRightNowYesStartDate): self
    {
        $this->followingEducationRightNowYesStartDate = $followingEducationRightNowYesStartDate;

        return $this;
    }

    public function getFollowingEducationRightNowYesEndDate(): ?\DateTimeInterface
    {
        return $this->followingEducationRightNowYesEndDate;
    }

    public function setFollowingEducationRightNowYesEndDate(?\DateTimeInterface $followingEducationRightNowYesEndDate): self
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

    public function getFollowingEducationRightNowNoEndDate(): ?\DateTimeInterface
    {
        return $this->followingEducationRightNowNoEndDate;
    }

    public function setFollowingEducationRightNowNoEndDate(?\DateTimeInterface $followingEducationRightNowNoEndDate): self
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
