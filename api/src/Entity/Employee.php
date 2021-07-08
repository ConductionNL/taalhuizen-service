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
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
 * @ApiFilter(SearchFilter::class, properties={
 *     "languageHouseId": "exact",
 *     "providerId": "exact"
 * })
 */
class Employee
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
     * @var ?string Contact telephone of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $contactTelephone;

    /**
     * @var ?string Contact Preference of this Employee.
     *
     * @Assert\Choice(
     *      {"PHONECALL","WHATSAPP","EMAIL","OTHER"}
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"PHONECALL", "WHATSAPP", "EMAIL", "OTHER"},
     *             "example"="PHONECALL"
     *         }
     *     }
     * )
     */
    private ?string $contactPreference;

    /**
     * @var ?string The Contact preference other of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $contactPreferenceOther;

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
     * @var ?string Currently following course name of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $currentlyFollowingCourseName;

    /**
     * @var ?string Currently following course institute of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $currentlyFollowingCourseInstitute;

    /***
     * @var ?string Currently following course teacher professionalism of this Employee.
     *
     * @Assert\Choice(
     *      {"PROFESSIONAL","VOLUNTEER","BOTH"}
     * )
     * @Groups({"read", "write"})
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
    private ?string $currentlyFollowingCourseTeacherProfessionalism;

    /**
     * @var ?string Currently following course professionalism of this Employee.
     *
     * @Assert\Choice(
     *      {"PROFESSIONAL","VOLUNTEER","BOTH"}
     * )
     * @Groups({"read", "write"})
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
    private ?string $currentlyFollowingCourseCourseProfessionalism;

    /**
     * @var ?bool Does currently follow course provide certificate of this Employee.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $doesCurrentlyFollowingCourseProvideCertificate;

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
     * @var ?Organization Organization of this person
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Organization::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Organization $organization;

    /**
     * @var ?string Bisc employee id of this Employee.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $employeeId;

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

    public function getContactTelephone(): ?string
    {
        return $this->contactTelephone;
    }

    public function setContactTelephone(?string $contactTelephone): self
    {
        $this->contactTelephone = $contactTelephone;

        return $this;
    }

    public function getContactPreference(): ?string
    {
        return $this->contactPreference;
    }

    public function setContactPreference(?string $contactPreference): self
    {
        $this->contactPreference = $contactPreference;

        return $this;
    }

    public function getTargetGroupPreferences(): ?array
    {
        return $this->targetGroupPreferences;
    }

    public function setTargetGroupPreferences(?array $targetGroupPreferences): self
    {
        $this->targetGroupPreferences = $targetGroupPreferences;

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

    public function getContactPreferenceOther(): ?string
    {
        return $this->contactPreferenceOther;
    }

    public function setContactPreferenceOther(?string $contactPreferenceOther): self
    {
        $this->contactPreferenceOther = $contactPreferenceOther;

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

    public function getCurrentlyFollowingCourseName(): ?string
    {
        return $this->currentlyFollowingCourseName;
    }

    public function setCurrentlyFollowingCourseName(?string $currentlyFollowingCourseName): self
    {
        $this->currentlyFollowingCourseName = $currentlyFollowingCourseName;

        return $this;
    }

    public function getCurrentlyFollowingCourseInstitute(): ?string
    {
        return $this->currentlyFollowingCourseInstitute;
    }

    public function setCurrentlyFollowingCourseInstitute(?string $currentlyFollowingCourseInstitute): self
    {
        $this->currentlyFollowingCourseInstitute = $currentlyFollowingCourseInstitute;

        return $this;
    }

    public function getCurrentlyFollowingCourseTeacherProfessionalism(): ?array
    {
        return $this->currentlyFollowingCourseTeacherProfessionalism;
    }

    public function setCurrentlyFollowingCourseTeacherProfessionalism(?array $currentlyFollowingCourseTeacherProfessionalism): self
    {
        $this->currentlyFollowingCourseTeacherProfessionalism = $currentlyFollowingCourseTeacherProfessionalism;

        return $this;
    }

    public function getCurrentlyFollowingCourseCourseProfessionalism(): ?array
    {
        return $this->currentlyFollowingCourseCourseProfessionalism;
    }

    public function setCurrentlyFollowingCourseCourseProfessionalism(?array $currentlyFollowingCourseCourseProfessionalism): self
    {
        $this->currentlyFollowingCourseCourseProfessionalism = $currentlyFollowingCourseCourseProfessionalism;

        return $this;
    }

    public function getDoesCurrentlyFollowingCourseProvideCertificate(): ?bool
    {
        return $this->doesCurrentlyFollowingCourseProvideCertificate;
    }

    public function setDoesCurrentlyFollowingCourseProvideCertificate(?bool $doesCurrentlyFollowingCourseProvideCertificate): self
    {
        $this->doesCurrentlyFollowingCourseProvideCertificate = $doesCurrentlyFollowingCourseProvideCertificate;

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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getEmployeeId(): ?string
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?string $employeeId): self
    {
        $this->employeeId = $employeeId;

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
