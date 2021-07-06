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

/**
 * @ApiResource(
 *     graphql={
 *          "item_query" = {
 *              "item_query" = EmployeeQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = EmployeeQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = EmployeeMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = EmployeeMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = EmployeeMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "addMentoredParticipationTo" = {
 *              "mutation" = EmployeeMutationResolver::class,
 *              "args" = {"participationId"={"type" = "ID!"}, "aanbiederEmployeeId"={"type" = "ID!"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     }
 * )
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
    private $id;

    /**
     * @var Person Person of this employee
     *
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private Person $person;

    /**
     * @var ?Address Address of this employee
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Address::class, cascade={"persist", "remove"})
     */
    private ?Address $addresses;

    /**
     * @var ?Telephone Telephone of this employee
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Telephone::class, cascade={"persist", "remove"})
     */
    private ?Telephone $telephones;

    /**
     * @var ?Email Email of this employee
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Email::class, cascade={"persist", "remove"})
     */
    private ?Email $emails;

    /**
     * @var string Contact telephone of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactTelephone;

    /**
     * @var string|null Contact Preference of this Employee.**PHONECALL**, **WHATSAPP**, **EMAIL**, **OTHER**
     *
     * @Assert\Choice(
     *      {"PHONECALL","WHATSAPP","EMAIL","OTHER"}
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $contactPreference;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactPreferenceOther;

    // @todo do we want the availability as a object?
    /**
     * @var array|null The availability for this employee
     * @Groups({"read", "write"})
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $availability = [];

    // @todo do we want the availability note as a object?
    /**
     * @var string The Availability Note of this Employee.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $availabilityNotes;

    /**
     * @var array|null Target Preference of this Employee. **NT1**, **NT2**
     *
     * @example NT1
     *
     * @Assert\Choice(
     *      {"NT1","NT2"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="json", length=255)
     */
    private ?array $targetGroupPreferences = [];

    /**
     * @var string|null Volunteering Preference of this Employee.
     *
     *  @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $volunteeringPreference = null;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gotHereVia;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hasExperienceWithTargetGroup;

    /**
     * @var bool Shouldn't this be a string to provide the reason for the experience with the target group?
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $experienceWithTargetGroupYesReason;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentEducation;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $currentEducationYes = [];

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $currentEducationNoButDidFollow = [];

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $doesCurrentlyFollowCourse;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentlyFollowingCourseName;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentlyFollowingCourseInstitute;

    /***
     * @Assert\Choice(
     *      {"PROFESSIONAL","VOLUNTEER","BOTH"}
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentlyFollowingCourseTeacherProfessionalism;

    /**
     * @Assert\Choice(
     *      {"PROFESSIONAL","VOLUNTEER","BOTH"}
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentlyFollowingCourseCourseProfessionalism;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $doesCurrentlyFollowingCourseProvideCertificate;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $otherRelevantCertificates;

    /**
     * @var bool|null Whether the employee has submitted a police certificate
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isVOGChecked = false;

    /**
     * @var ?Organization Organization of this person
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Organization::class, cascade={"persist", "remove"})
     */
    private ?Organization $organization;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $biscEmployeeId;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userId;

    /**
     * @var array|null The user roles of this employee
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $userRoles = [];

    /**
     * @var Datetime The moment this resource was created
     *
     * @Groups({"read", "write"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateCreated;

    /**
     * @var Datetime The moment this resource last Modified
     *
     * @Groups({"read", "write"})
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateModified;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
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

    public function getAddresses(): ?Address
    {
        return $this->addresses;
    }

    public function setAddresses(?Address $addresses): self
    {
        $this->addresses = $addresses;

        return $this;
    }

    public function getTelephones(): ?Telephone
    {
        return $this->telephones;
    }

    public function setTelephones(?Telephone $telephones): self
    {
        $this->telephones = $telephones;

        return $this;
    }

    public function getEmails(): ?Email
    {
        return $this->emails;
    }

    public function setEmails(?Email $emails): self
    {
        $this->emails = $emails;

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

    public function getCurrentlyFollowingCourseTeacherProfessionalism(): ?string
    {
        return $this->currentlyFollowingCourseTeacherProfessionalism;
    }

    public function setCurrentlyFollowingCourseTeacherProfessionalism(?string $currentlyFollowingCourseTeacherProfessionalism): self
    {
        $this->currentlyFollowingCourseTeacherProfessionalism = $currentlyFollowingCourseTeacherProfessionalism;

        return $this;
    }

    public function getCurrentlyFollowingCourseCourseProfessionalism(): ?string
    {
        return $this->currentlyFollowingCourseCourseProfessionalism;
    }

    public function setCurrentlyFollowingCourseCourseProfessionalism(?string $currentlyFollowingCourseCourseProfessionalism): self
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

    public function getAvailability(): ?array
    {
        return $this->availability;
    }

    public function setAvailability(?array $availability = []): self
    {
        $this->availability = $availability;

        return $this;
    }

    public function getCurrentEducationYes(): ?array
    {
        return $this->currentEducationYes;
    }

    public function setCurrentEducationYes(?array $currentEducationYes = []): self
    {
        $this->currentEducationYes = $currentEducationYes;

        return $this;
    }

    public function getCurrentEducationNoButDidFollow(): ?array
    {
        return $this->currentEducationNoButDidFollow;
    }

    public function setCurrentEducationNoButDidFollow(?array $currentEducationNoButDidFollow = []): self
    {
        $this->currentEducationNoButDidFollow = $currentEducationNoButDidFollow;

        return $this;
    }

    public function getBiscEmployeeId(): ?string
    {
        return $this->biscEmployeeId;
    }

    public function setBiscEmployeeId(?string $biscEmployeeId): self
    {
        $this->biscEmployeeId = $biscEmployeeId;

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

    public function getUserRoles(): ?array
    {
        return $this->userRoles;
    }

    public function setUserRoles(?array $userRoles): self
    {
        $this->userRoles = $userRoles;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(\DateTimeInterface $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }

}
