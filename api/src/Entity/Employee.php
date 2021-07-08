<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\EmployeeRepository;
use App\Resolver\EmployeeMutationResolver;
use App\Resolver\EmployeeQueryCollectionResolver;
use App\Resolver\EmployeeQueryItemResolver;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=EmployeeRepository::class)
 */
class Employee
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
     * @var Person Person of this employee
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @MaxDepth(1)
     */
    private Person $person;

    /**
     * @var ?Availability The Availability of this Employee.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Availability::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Availability $availability;

    /**
     * @var ?string The Availability Note of this Employee.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private ?string $availabilityNotes;

    /**
     * @var ?array Target Group Preference of this Employee.
     *
     * @example NT1
     * @Assert\Choice(multiple=true, choices={"NT1","NT2"})
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="array", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="array",
     *             "items"={
     *               "type"="string",
     *               "enum"={"NT1", "NT2"},
     *               "example"="NT1"
     *             }
     *         }
     *     }
     * )
     */
    private ?array $targetGroupPreferences = [];

    /**
     * @var ?string Volunteering Preference of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $volunteeringPreference = null;

    /**
     * @var ?string Got here via of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $gotHereVia;

    /**
     * @var ?string Has experience with target group of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $hasExperienceWithTargetGroup;

    /**
     * @var ?bool Shouldn't this be a string to provide the reason for the experience with the target group?
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $experienceWithTargetGroupYesReason;

    /**
     * @var ?string Current education of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $currentEducation;

//   Education of the employee, was called in the graphql-schema 'currentEducationYes' and 'currentEducationNoButDidFollow', changed to 'education'(Education entity) related to schema.org
    /**
     * @var ?Education Education of this employee
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Education::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Education $education;

    /**
     * @var ?bool Does currently follow course of this Employee.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $doesCurrentlyFollowCourse;

    /**
     * @var ?Education Currently following course (Education) of this Employee.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Education::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Education $followingCourse;

    /**
     * @var ?string Other relevant certificates of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $otherRelevantCertificates;

    /**
     * @var ?bool Whether the employee has submitted a police certificate
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isVOGChecked = false;

    /**
     * @var String|null A contact component organization id of this Employee.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $organizationId;

    /**
     * @var ?string User id of this Employee.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $userId;

    /**
     * @var ?array User Group ids of this Employee.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $userGroupIds = [];

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getAvailability(): ?Availability
    {
        return $this->availability;
    }

    public function setAvailability(?Availability $availability): self
    {
        $this->availability = $availability;

        return $this;
    }

    public function getAvailabilityNotes(): ?string
    {
        return $this->availabilityNotes;
    }

    public function setAvailabilityNotes(?string $availabilityNotes): self
    {
        $this->availabilityNotes = $availabilityNotes;

        return $this;
    }

    public function getVolunteeringPreference(): ?string
    {
        return $this->volunteeringPreference;
    }

    public function setVolunteeringPreference(?string $volunteeringPreference): self
    {
        $this->volunteeringPreference = $volunteeringPreference;

        return $this;
    }

    public function getUserGroupIds(): ?array
    {
        return $this->userGroupIds;
    }

    public function setUserGroupIds(?array $userGroupIds): self
    {
        $this->userGroupIds = $userGroupIds;

        return $this;
    }

    public function getGotHereVia(): ?string
    {
        return $this->gotHereVia;
    }

    public function setGotHereVia(?string $gotHereVia): self
    {
        $this->gotHereVia = $gotHereVia;

        return $this;
    }

    public function getHasExperienceWithTargetGroup(): ?bool
    {
        return $this->hasExperienceWithTargetGroup;
    }

    public function setHasExperienceWithTargetGroup(?bool $hasExperienceWithTargetGroup): self
    {
        $this->hasExperienceWithTargetGroup = $hasExperienceWithTargetGroup;

        return $this;
    }

    public function getExperienceWithTargetGroupYesReason(): ?bool
    {
        return $this->experienceWithTargetGroupYesReason;
    }

    public function setExperienceWithTargetGroupYesReason(?bool $experienceWithTargetGroupYesReason): self
    {
        $this->experienceWithTargetGroupYesReason = $experienceWithTargetGroupYesReason;

        return $this;
    }

    public function getCurrentEducation(): ?string
    {
        return $this->currentEducation;
    }

    public function setCurrentEducation(?string $currentEducation): self
    {
        $this->currentEducation = $currentEducation;

        return $this;
    }

    public function getDoesCurrentlyFollowCourse(): ?bool
    {
        return $this->doesCurrentlyFollowCourse;
    }

    public function setDoesCurrentlyFollowCourse(?bool $doesCurrentlyFollowCourse): self
    {
        $this->doesCurrentlyFollowCourse = $doesCurrentlyFollowCourse;

        return $this;
    }

    public function getFollowingCourse(): ?Education
    {
        return $this->followingCourse;
    }

    public function setFollowingCourse(?Education $followingCourse): self
    {
        $this->followingCourse = $followingCourse;

        return $this;
    }

    public function getOtherRelevantCertificates(): ?string
    {
        return $this->otherRelevantCertificates;
    }

    public function setOtherRelevantCertificates(?string $otherRelevantCertificates): self
    {
        $this->otherRelevantCertificates = $otherRelevantCertificates;

        return $this;
    }

    public function getIsVOGChecked(): ?bool
    {
        return $this->isVOGChecked;
    }

    public function setIsVOGChecked(?bool $isVOGChecked = false): self
    {
        $this->isVOGChecked = $isVOGChecked;

        return $this;
    }

    public function getOrganizationId(): ?string
    {
        return $this->organizationId;
    }

    public function setOrganizationId(?string $organizationId): self
    {
        $this->organizationId = $organizationId;

        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

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
