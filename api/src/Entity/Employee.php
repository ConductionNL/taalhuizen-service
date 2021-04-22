<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use App\Resolver\EmployeeQueryItemResolver;
use App\Resolver\EmployeeQueryCollectionResolver;
use App\Resolver\EmployeeMutationResolver;
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
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass=EmployeeRepository::class)
 * @Gedmo\Loggable(logEntryClass="Conduction\CommonGroundBundle\Entity\ChangeLog")
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
     * @var string The Name of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $givenName;

    /**
     * @var string The PrefixName of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $additionalName;

    /**
     * @var string The LastName of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $familyName;

    /**
     * @var string The Telephone of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telephone;

    /**
     * @ORM\OneToOne(targetEntity=Availability::class, cascade={"persist", "remove"})
     */
    private $availability;

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
     * @var string The Email of this Employee.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $userGroupIds = [];

    /**
     * @var string The Gender of this Employee. **Male**, **Female**, **X**
     *
     * @example Male
     *
     * @Assert\Choice(
     *      {"Male","Female","X"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gender;

    /**
     * @var string Date of birth of this Employee.
     *
     * @example 15-03-2000
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateOfBirth;

    /**
     * @var Address The address of this Employee.
     *
     * @MaxDepth(1)
     * @Groups({"read", "write"})
     * @ORM\ManyToMany(targetEntity=Address::class)
     */
    private $address;

    /**
     * @var string Contact Telephone of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactTelephone;

    /**
     * @var string Contact Preference of this Employee.**PHONECALL**, **WHATSAPP**, **EMAIL**, **OTHER**
     *
     * @Assert\Choice(
     *      {"PHONECALL","WHATSAPP","EMAIL","OTHER"}
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactPreference;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactPreferenceOther;

    /**
     * @var array Target Preference of this Employee. **NT1**, **NT2**
     *
     * @example NT1
     *
     * @Assert\Choice(
     *      {"NT1","NT2"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private $targetGroupPreference = [];

    /**
     * @var string Voluntering Preference of this Employee.
     *
     *  @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $volunteringPreference;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gotHereVia;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hasExperienceWithTargetGroup;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $experienceWithTargetGroupYesReason;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentEducation;

    /**
     * @MaxDepth(1)
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=CurrentEducationYes::class, cascade={"persist", "remove"})
     */
    private $currentEducationYes;

    /**
     *
     * @MaxDepth(1)
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=CurrentEducationNoButDidFollow::class, cascade={"persist", "remove"})
     */
    private $currentEducationNoButDidFollow;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $doesCurrentlyFollowCourse;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentlyFollowingCourseName;

    /**
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
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $doesCurrentlyFollowingCourseProvideCertificate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $otherRelevantCertificates;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVOGChecked;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $taalhuisId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $biscEmployeeId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userGroupId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userId;

    public function __construct()
    {
        $this->address = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setName(string $givenName): self
    {
        $this->givenName = $givenName;

        return $this;
    }

    public function getAdditionalName(): ?string
    {
        return $this->additionalName;
    }

    public function setAdditionalName(?string $additionalName): self
    {
        $this->additionalName = $additionalName;

        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(string $familyName): self
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeInterface $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;

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

    public function getTargetGroupPreference(): ?array
    {
        return $this->targetGroupPreference;
    }

    public function setTargetGroupPreference(?array $targetGroupPreference): self
    {
        $this->targetGroupPreference = $targetGroupPreference;

        return $this;
    }

    public function getVolunteringPreference(): ?string
    {
        return $this->volunteringPreference;
    }

    public function setVolunteringPreference(?string $volunteringPreference): self
    {
        $this->volunteringPreference = $volunteringPreference;

        return $this;
    }

    /**
     * @return Collection|Address[]
     */
    public function getAddress(): Collection
    {
        return $this->address;
    }

    public function addAddress(Address $address): self
    {
        if (!$this->address->contains($address)) {
            $this->address[] = $address;
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        $this->address->removeElement($address);

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

    public function setIsVOGChecked(bool $isVOGChecked): self
    {
        $this->isVOGChecked = $isVOGChecked;

        return $this;
    }

    public function getAanbiederId(): ?string
    {
        return $this->aanbiederId;
    }

    public function setAanbiederId(?string $aanbiederId): self
    {
        $this->aanbiederId = $aanbiederId;

        return $this;
    }

    public function getTaalhuisId(): ?string
    {
        return $this->taalhuisId;
    }

    public function setTaalhuisId(?string $taalhuisId): self
    {
        $this->taalhuisId = $taalhuisId;

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

    public function getCurrentEducationYes(): ?CurrentEducationYes
    {
        return $this->currentEducationYes;
    }

    public function setCurrentEducationYes(?CurrentEducationYes $currentEducationYes): self
    {
        $this->currentEducationYes = $currentEducationYes;

        return $this;
    }

    public function getCurrentEducationNoButDidFollow(): ?CurrentEducationNoButDidFollow
    {
        return $this->currentEducationNoButDidFollow;
    }

    public function setCurrentEducationNoButDidFollow(?CurrentEducationNoButDidFollow $currentEducationNoButDidFollow): self
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

    public function getUserGroupId(): ?string
    {
        return $this->userGroupId;
    }

    public function setUserGroupId(?string $userGroupId): self
    {
        $this->userGroupId = $userGroupId;

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
}
