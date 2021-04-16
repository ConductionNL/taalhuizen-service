<?php

namespace App\Entity;

use App\Repository\StudentRepository;
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
 * @ORM\Entity(repositoryClass=StudentRepository::class)
 */
class Student
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
     * @ORM\OneToOne(targetEntity=StudentCivicIntegration::class, cascade={"persist", "remove"})
     */
    private $civicIntegrationDetails;

    /**
     *
     * @Assert\NotNull
     * @MaxDepth(1)
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentPerson::class, cascade={"persist", "remove"})
     */
    private $personDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentContact::class, cascade={"persist", "remove"})
     */
    private $contactDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentGeneral::class, cascade={"persist", "remove"})
     */
    private $generalDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentReferrer::class, cascade={"persist", "remove"})
     */
    private $referrerDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentBackground::class, cascade={"persist", "remove"})
     */
    private $backgroundDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentDutchNT::class, cascade={"persist", "remove"})
     */
    private $dutchNTDetails;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $speakingLevel;

    /**
     * @ORM\OneToOne(targetEntity=StudentEducation::class, cascade={"persist", "remove"})
     */
    private $educationDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentCourse::class, cascade={"persist", "remove"})
     */
    private $courseDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentJob::class, cascade={"persist", "remove"})
     */
    private $jobDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentMotivation::class, cascade={"persist", "remove"})
     */
    private $motivationDetails;

    /**
     * @ORM\OneToOne(targetEntity=StudentAvailability::class, cascade={"persist", "remove"})
     */
    private $availabilityDetails;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $readingTestResult;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $writingTestResult;

    /**
     * @Assert\NotNull
     * @MaxDepth(1)
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentPermission::class, cascade={"persist", "remove"})
     */
    private $permissionDetails;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $taalhuisId;

    public function getCivicIntegrationDetails(): ?StudentCivicIntegration
    {
        return $this->civicIntegrationDetails;
    }

    public function setCivicIntegrationDetails(?StudentCivicIntegration $civicIntegrationDetails): self
    {
        $this->civicIntegrationDetails = $civicIntegrationDetails;

        return $this;
    }

    public function getPersonDetails(): ?StudentPerson
    {
        return $this->personDetails;
    }

    public function setPersonDetails(?StudentPerson $personDetails): self
    {
        $this->personDetails = $personDetails;

        return $this;
    }

    public function getContactDetails(): ?StudentContact
    {
        return $this->contactDetails;
    }

    public function setContactDetails(?StudentContact $contactDetails): self
    {
        $this->contactDetails = $contactDetails;

        return $this;
    }

    public function getGeneralDetails(): ?StudentGeneral
    {
        return $this->generalDetails;
    }

    public function setGeneralDetails(?StudentGeneral $generalDetails): self
    {
        $this->generalDetails = $generalDetails;

        return $this;
    }

    public function getReferrerDetails(): ?StudentReferrer
    {
        return $this->referrerDetails;
    }

    public function setReferrerDetails(?StudentReferrer $referrerDetails): self
    {
        $this->referrerDetails = $referrerDetails;

        return $this;
    }

    public function getBackgroundDetails(): ?StudentBackground
    {
        return $this->backgroundDetails;
    }

    public function setBackgroundDetails(?StudentBackground $backgroundDetails): self
    {
        $this->backgroundDetails = $backgroundDetails;

        return $this;
    }

    public function getDutchNTDetails(): ?StudentDutchNT
    {
        return $this->dutchNTDetails;
    }

    public function setDutchNTDetails(?StudentDutchNT $dutchNTDetails): self
    {
        $this->dutchNTDetails = $dutchNTDetails;

        return $this;
    }

    public function getSpeakingLevel(): ?string
    {
        return $this->speakingLevel;
    }

    public function setSpeakingLevel(?string $speakingLevel): self
    {
        $this->speakingLevel = $speakingLevel;

        return $this;
    }

    public function getEducationDetails(): ?StudentEducation
    {
        return $this->educationDetails;
    }

    public function setEducationDetails(?StudentEducation $educationDetails): self
    {
        $this->educationDetails = $educationDetails;

        return $this;
    }

    public function getCourseDetails(): ?StudentCourse
    {
        return $this->courseDetails;
    }

    public function setCourseDetails(?StudentCourse $courseDetails): self
    {
        $this->courseDetails = $courseDetails;

        return $this;
    }

    public function getJobDetails(): ?StudentJob
    {
        return $this->jobDetails;
    }

    public function setJobDetails(?StudentJob $jobDetails): self
    {
        $this->jobDetails = $jobDetails;

        return $this;
    }

    public function getMotivationDetails(): ?StudentMotivation
    {
        return $this->motivationDetails;
    }

    public function setMotivationDetails(?StudentMotivation $motivationDetails): self
    {
        $this->motivationDetails = $motivationDetails;

        return $this;
    }

    public function getAvailabilityDetails(): ?StudentAvailability
    {
        return $this->availabilityDetails;
    }

    public function setAvailabilityDetails(?StudentAvailability $availabilityDetails): self
    {
        $this->availabilityDetails = $availabilityDetails;

        return $this;
    }

    public function getReadingTestResult(): ?string
    {
        return $this->readingTestResult;
    }

    public function setReadingTestResult(?string $readingTestResult): self
    {
        $this->readingTestResult = $readingTestResult;

        return $this;
    }

    public function getWritingTestResult(): ?string
    {
        return $this->writingTestResult;
    }

    public function setWritingTestResult(?string $writingTestResult): self
    {
        $this->writingTestResult = $writingTestResult;

        return $this;
    }

    public function getPermissionDetails(): ?StudentPermission
    {
        return $this->permissionDetails;
    }

    public function setPermissionDetails(?StudentPermission $permissionDetails): self
    {
        $this->permissionDetails = $permissionDetails;

        return $this;
    }

    public function getTaalhuisId(): ?string
    {
        return $this->taalhuisId;
    }

    public function setTaalhuisId(string $taalhuisId): self
    {
        $this->taalhuisId = $taalhuisId;

        return $this;
    }

}
