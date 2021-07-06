<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\StudentRepository;
use App\Resolver\StudentMutationResolver;
use App\Resolver\StudentQueryCollectionResolver;
use App\Resolver\StudentQueryItemResolver;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
 *     },
 *     graphql={
 *          "item_query" = {
 *              "item_query" = StudentQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = StudentQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = StudentMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = StudentMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = StudentMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "newReffered" = {
 *              "collection_query" = StudentQueryCollectionResolver::class
 *          },
 *          "active" = {
 *              "collection_query" = StudentQueryCollectionResolver::class
 *          },
 *          "completed" = {
 *              "collection_query" = StudentQueryCollectionResolver::class
 *          },
 *          "group" = {
 *              "collection_query" = StudentQueryCollectionResolver::class
 *          },
 *          "providerEmployeeMentees" = {
 *              "collection_query" = StudentQueryCollectionResolver::class
 *          }
 *     }
 * )
 * @ApiFilter(SearchFilter::class, properties={"languageHouseId": "exact", "providerId": "exact", "groupId": "exact", "providerEmployeeId": "exact"})
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
    private UuidInterface $id;

    /**
     * @var String|null The status of this Student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"pending", "accepted"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $status;

    /**
     * @var String|null The memo of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $memo;

    /**
     * @var Person|null A contact catalogue person for the registrar, this person should have a Organization with at least the name set.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?Person $registrar;

    /**
     * @var StudentCivicIntegration|null The StudentCivicIntegration of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentCivicIntegration::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentCivicIntegration $civicIntegrationDetails;

    /**
     * @var Person|null A contact catalogue person for the student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private Person $person;

    /**
     * @var StudentGeneral|null The StudentGeneral of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentGeneral::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentGeneral $generalDetails;

    /**
     * @var StudentReferrer|null The StudentReferrer of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentReferrer::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentReferrer $referrerDetails;

    /**
     * @var StudentBackground|null The StudentBackground of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentBackground::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentBackground $backgroundDetails;

    /**
     * @var StudentDutchNT|null The StudentDutchNT of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentDutchNT::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentDutchNT $dutchNTDetails;

    /**
     * @var String|null The speakingLevel of this Student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"BEGINNER", "REASONABLE", "ADVANCED"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $speakingLevel;

    /**
     * @var StudentEducation|null The StudentEducation of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentEducation::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentEducation $educationDetails;

    /**
     * @var StudentCourse|null The StudentCourse of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentCourse::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentCourse $courseDetails;

    /**
     * @var StudentJob|null The StudentJob of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentJob::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentJob $jobDetails;

    /**
     * @var StudentMotivation|null The StudentMotivation of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentMotivation::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentMotivation $motivationDetails;

    /**
     * @var StudentAvailability|null The StudentAvailability of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentAvailability::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?StudentAvailability $availabilityDetails;

    /**
     * @Groups({"read", "write"})
     * @Assert\Choice({"CAN_NOT_READ", "A0", "A1", "A2", "B1", "B2", "C1", "C2"})
     * @ORM\Column(type="json", length=255, nullable=true)
     */
    private ?string $readingTestResult;

    /**
     * @Groups({"read", "write"})
     * @Assert\Choice({"CAN_NOT_WRITE", "WRITE_NAW_DETAILS", "WRITE_SIMPLE_TEXTS", "WRITE_SIMPLE_LETTERS"})
     * @ORM\Column(type="json", length=255, nullable=true)
     */
    private ?string $writingTestResult;

    /**
     * @var StudentPermission|null The StudentPermission of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=StudentPermission::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private StudentPermission $permissionDetails;

    /**
     * @var string|null The id of the cc/organization of a languageHouse.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $languageHouseId;

    /**
     * @var string|null The id of the cc/organization of a provider.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $providerId;

    /**
     * @var string|null The id of the edu/group of a group.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $groupId;

    /**
     * @var string|null The id of the mrc/employee of a mentor.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $providerEmployeeId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

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

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): self
    {
        $this->memo = $memo;

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

    public function getLanguageHouseId(): ?string
    {
        return $this->languageHouseId;
    }

    public function setLanguageHouseId(?string $languageHouseId): self
    {
        $this->languageHouseId = $languageHouseId;

        return $this;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(?string $providerId): self
    {
        $this->providerId = $providerId;

        return $this;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    public function getProviderEmployeeId(): ?string
    {
        return $this->providerEmployeeId;
    }

    public function setProviderEmployeeId(?string $providerEmployeeId): self
    {
        $this->providerEmployeeId = $providerEmployeeId;

        return $this;
    }
}
