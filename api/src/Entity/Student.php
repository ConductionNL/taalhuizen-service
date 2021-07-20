<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\StudentRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity Student holds.
 *
 * The main entity associated with this DTO is the edu/Participant: https://taalhuizen-bisc.commonground.nu/api/v1/edu#tag/Participant.
 * DTO Student exists of a few properties based on this education component entity, that is based on the following schema.org schema: https://schema.org/Participant.
 * But the other main source that properties of this Student entity are based on, is the following jira epic: https://lifely.atlassian.net/browse/BISC-60.
 * And mainly the following issue: https://lifely.atlassian.net/browse/BISC-76.
 * The registrar and person (PersonDetails + ContactDetails) input fields match the Person Entity, that is why there are two Person objects used here instead of matching the exact properties in the graphql schema.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "put",
 *          "delete"
 *     },
 *     collectionOperations={
 *          "get",
 *          "get_group_students"={
 *              "method"="GET",
 *              "path"="/students/group/{uuid}",
 *              "swagger_context" = {
 *                  "summary"="Get the students of a group",
 *                  "description"="Get the students of a group"
 *              }
 *          },
 *          "get_mentor_students"={
 *              "method"="GET",
 *              "path"="/students/mentor/{uuid}",
 *              "swagger_context" = {
 *                  "summary"="Get the students of a mentor",
 *                  "description"="Get the students of a mentor"
 *              }
 *          },
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=StudentRepository::class)
 * @ApiFilter(SearchFilter::class, properties={
 *     "status": "exact"
 * })
 */
class Student
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
     * @var Person|null A contact catalogue person for the registrar, this person should have a Organization with at least the name set.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Person $registrar;

    /**
     * @var StudentCivicIntegration|null The StudentCivicIntegration of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentCivicIntegration::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentCivicIntegration $civicIntegrationDetails;

    /**
     * @var Person A contact catalogue person for the student.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private Person $person;

    /**
     * @var StudentGeneral|null The StudentGeneral of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentGeneral::class)
     * @ApiSubresource()
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentGeneral $generalDetails;

    /**
     * @var StudentReferrer|null The StudentReferrer of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentReferrer::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentReferrer $referrerDetails;

    /**
     * @var StudentBackground|null The StudentBackground of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentBackground::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentBackground $backgroundDetails;

    /**
     * @var StudentDutchNT|null The StudentDutchNT of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentDutchNT::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentDutchNT $dutchNTDetails;

    /**
     * @var string|null The speakingLevel of this Student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"BEGINNER", "REASONABLE", "ADVANCED"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"BEGINNER", "REASONABLE", "ADVANCED"},
     *             "example"="BEGINNER"
     *         }
     *     }
     * )
     */
    private ?string $speakingLevel;

    /**
     * @var StudentEducation|null The StudentEducation of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentEducation::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentEducation $educationDetails;

    /**
     * @var StudentCourse|null The StudentCourse of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentCourse::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentCourse $courseDetails;

    /**
     * @var StudentJob|null The StudentJob of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentJob::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentJob $jobDetails;

    /**
     * @var StudentMotivation|null The StudentMotivation of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentMotivation::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentMotivation $motivationDetails;

    /**
     * @var StudentAvailability|null The StudentAvailability of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentAvailability::class)
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?StudentAvailability $availabilityDetails;

    /**
     * @var string|null The reading test result of this Student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"CAN_NOT_READ", "A0", "A1", "A2", "B1", "B2", "C1", "C2"})
     * @ORM\Column(type="json", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"CAN_NOT_READ", "A0", "A1", "A2", "B1", "B2", "C1", "C2"},
     *             "example"="A0"
     *         }
     *     }
     * )
     */
    private ?string $readingTestResult;

    /**
     * @var string|null The writing test result of this Student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"CAN_NOT_WRITE", "WRITE_NAW_DETAILS", "WRITE_SIMPLE_TEXTS", "WRITE_SIMPLE_LETTERS"})
     * @ORM\Column(type="json", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"CAN_NOT_WRITE", "WRITE_NAW_DETAILS", "WRITE_SIMPLE_TEXTS", "WRITE_SIMPLE_LETTERS"},
     *             "example"="WRITE_NAW_DETAILS"
     *         }
     *     }
     * )
     */
    private ?string $writingTestResult;

    /**
     * @var StudentPermission The StudentPermission of this Student.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentPermission::class)
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private StudentPermission $permissionDetails;

    /**
     * @var string The id of the cc/organization of a languageHouse
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $languageHouseId;

    /**
     * @var string|null The Status of this group.
     *
     * @Groups({"read"})
     * @Assert\Choice({"REFERRED", "ACTIVE", "COMPLETED"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"REFERRRED", "ACTIVE", "COMPLETED"},
     *             "example"="REFERRED"
     *         }
     *     }
     * )
     */
    private ?string $status;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getRegistrar(): ?Person
    {
        return $this->registrar;
    }

    public function setRegistrar(?Person $registrar): self
    {
        $this->registrar = $registrar;

        return $this;
    }

    public function getCivicIntegrationDetails(): ?StudentCivicIntegration
    {
        return $this->civicIntegrationDetails;
    }

    public function setCivicIntegrationDetails(?StudentCivicIntegration $civicIntegrationDetails): self
    {
        $this->civicIntegrationDetails = $civicIntegrationDetails;

        return $this;
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): self
    {
        $this->person = $person;

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

    public function getPermissionDetails(): StudentPermission
    {
        return $this->permissionDetails;
    }

    public function setPermissionDetails(StudentPermission $permissionDetails): self
    {
        $this->permissionDetails = $permissionDetails;

        return $this;
    }

    public function getLanguageHouseId(): string
    {
        return $this->languageHouseId;
    }

    public function setLanguageHouseId(string $languageHouseId): self
    {
        $this->languageHouseId = $languageHouseId;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
