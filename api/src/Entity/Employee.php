<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=EmployeeRepository::class)
 */
class Employee
{
    /**
     * @var UuidInterface The UUID identifier of this Employee.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\Uuid
     * @Groups({"read"})
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
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string The PrefixName of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prefixName;

    /**
     * @var string The LastName of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @var string The Telephone of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telephone;

    /**
     * @var string The Availability Note of this Employee.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $availabilityNote;

    /**
     * @var string The Email of this Employee.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @var array The Role of this Employee.
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="array")
     */
    private $role = [];

    /**
     * @var array The Availability of this Employee.
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="array", nullable=true)
     */
    private $availability = [];

    /**
     * @var string The Gender of this Employee. **Male**, **Female**
     * @Gedmo\Versioned
     * @example Male
     *
     * @Assert\Choice(
     *      {"Male","Female"}
     * )
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gender;

    /**
     * @var string Date of birth of this Employee.
     *
     * @example 15-03-2000
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $birthday;

    /**
     * @var string The Birthplace of this Employee.
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $birthplace;

    /**
     * @var string The Street of this Employee.
     *
     * @example appelstreet
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $street;

    /**
     * @var string House number of this Employee.
     *
     * @example 8
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $houseNumber;

    /**
     * @var string House number suffix of this Employee.
     *
     * @example b
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $houseNumberSuffix;

    /**
     * @var string Postalcode of this Employee.
     *
     * @example 1234AB
     *
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $postalCode;

    /**
     * @var string Locality of this Employee.
     *
     * @example Oud-Zuid
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Gedmo\Versioned
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $locality;

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
     * @var string Contact Preference of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactPreference;

    /**
     * @var string Target Audience of this Employee. **NT1**, **NT2**
     *
     * @example NT1
     *
     * @Assert\Choice(
     *      {"NT1","NT2"}
     * )
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $targetAudience;

    /**
     * @var string Volunteer Preference of this Employee.
     *
     *  @Assert\Length(
     *     max = 255
     *)
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $volunteerPreference;

    /**
     * @var string Volunteer Note of this Employee.
     *
     * @Assert\Length(
     *     max = 2550
     *)
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $volunteerNote;

    /**
     * @var string Experience of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $experience;

    /**
     * @var Datetime StartDate Education of this Employee.
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startEducation;

    /**
     * @var string Education Institution Name of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $educationInstitutionName;

    /**
     * @var boolean Certificate Education of this Employee.
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $certificateEducation;

    /**
     * @var Datetime EndDate Education of this Employee.
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $endEducation;

    /**
     * @var string The Isced Education Level Code of this Employee.
     *
     * @example HBO
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $iscedEducationLevelCode;

    /**
     * @var boolean Certificate Course of this Employee.
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $degreeGrantedStatus;

    /**
     * @var string Course Name of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $courseName;

    /**
     * @var string Course Institution Name of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $courseInstitutionName;

    /**
     * @var string The Type of Teacher of this employee. **Professional**, **Volunteer**, **Both**
     *
     * @example Professional
     *
     * @Assert\Choice(
     *      {"Professional","Volunteer","Both"}
     * )
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $typeOfTeacher;

    /**
     * @var string The Type of Course of this employee. **Professional**, **Volunteer**, **Both**
     *
     * @example Professional
     *
     * @Assert\Choice(
     *      {"Professional","Volunteer","Both"}
     * )
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $typeOfCourse;

    /**
     * @var boolean Certificate Course of this Employee.
     *
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $certificateCourse;

    /**
     * @var string Relevant Additions of this Employee.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Gedmo\Versioned
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $relevantAdditions;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrefixName(): ?string
    {
        return $this->prefixName;
    }

    public function setPrefixName(?string $prefixName): self
    {
        $this->prefixName = $prefixName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

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

    public function getAvailabilityNote(): ?string
    {
        return $this->availabilityNote;
    }

    public function setAvailabilityNote(?string $availabilityNote): self
    {
        $this->availabilityNote = $availabilityNote;

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

    public function getRole(): ?array
    {
        return $this->role;
    }

    public function setRole(array $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getAvailability(): ?array
    {
        return $this->availability;
    }

    public function setAvailability(?array $availability): self
    {
        $this->availability = $availability;

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

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthplace(): ?string
    {
        return $this->birthplace;
    }

    public function setBirthplace(?string $birthplace): self
    {
        $this->birthplace = $birthplace;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(?string $houseNumber): self
    {
        $this->houseNumber = $houseNumber;

        return $this;
    }

    public function getHouseNumberSuffix(): ?string
    {
        return $this->houseNumberSuffix;
    }

    public function setHouseNumberSuffix(?string $houseNumberSuffix): self
    {
        $this->houseNumberSuffix = $houseNumberSuffix;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getLocality(): ?string
    {
        return $this->locality;
    }

    public function setLocality(?string $locality): self
    {
        $this->locality = $locality;

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

    public function getTargetAudience(): ?string
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(?string $targetAudience): self
    {
        $this->targetAudience = $targetAudience;

        return $this;
    }

    public function getVolunteerPreference(): ?string
    {
        return $this->volunteerPreference;
    }

    public function setVolunteerPreference(?string $volunteerPreference): self
    {
        $this->volunteerPreference = $volunteerPreference;

        return $this;
    }

    public function getVolunteerNote(): ?string
    {
        return $this->volunteerNote;
    }

    public function setVolunteerNote(?string $volunteerNote): self
    {
        $this->volunteerNote = $volunteerNote;

        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): self
    {
        $this->experience = $experience;

        return $this;
    }

    public function getStartEducation(): ?\DateTimeInterface
    {
        return $this->startEducation;
    }

    public function setStartEducation(?\DateTimeInterface $startEducation): self
    {
        $this->startEducation = $startEducation;

        return $this;
    }

    public function getEducationInstitutionName(): ?string
    {
        return $this->educationInstitutionName;
    }

    public function setEducationInstitutionName(?string $educationInstitutionName): self
    {
        $this->educationInstitutionName = $educationInstitutionName;

        return $this;
    }

    public function getCertificateEducation(): ?bool
    {
        return $this->certificateEducation;
    }

    public function setCertificateEducation(?bool $certificateEducation): self
    {
        $this->certificateEducation = $certificateEducation;

        return $this;
    }

    public function getEndEducation(): ?\DateTimeInterface
    {
        return $this->endEducation;
    }

    public function setEndEducation(?\DateTimeInterface $endEducation): self
    {
        $this->endEducation = $endEducation;

        return $this;
    }

    public function getIscedEducationLevelCode(): ?string
    {
        return $this->iscedEducationLevelCode;
    }

    public function setIscedEducationLevelCode(?string $iscedEducationLevelCode): self
    {
        $this->iscedEducationLevelCode = $iscedEducationLevelCode;

        return $this;
    }

    public function getDegreeGrantedStatus(): ?bool
    {
        return $this->degreeGrantedStatus;
    }

    public function setDegreeGrantedStatus(?bool $degreeGrantedStatus): self
    {
        $this->degreeGrantedStatus = $degreeGrantedStatus;

        return $this;
    }

    public function getCourseName(): ?string
    {
        return $this->courseName;
    }

    public function setCourseName(?string $courseName): self
    {
        $this->courseName = $courseName;

        return $this;
    }

    public function getCourseInstitutionName(): ?string
    {
        return $this->courseInstitutionName;
    }

    public function setCourseInstitutionName(?string $courseInstitutionName): self
    {
        $this->courseInstitutionName = $courseInstitutionName;

        return $this;
    }

    public function getTypeOfTeacher(): ?string
    {
        return $this->typeOfTeacher;
    }

    public function setTypeOfTeacher(?string $typeOfTeacher): self
    {
        $this->typeOfTeacher = $typeOfTeacher;

        return $this;
    }

    public function getTypeOfCourse(): ?string
    {
        return $this->typeOfCourse;
    }

    public function setTypeOfCourse(?string $typeOfCourse): self
    {
        $this->typeOfCourse = $typeOfCourse;

        return $this;
    }

    public function getCertificateCourse(): ?bool
    {
        return $this->certificateCourse;
    }

    public function setCertificateCourse(?bool $certificateCourse): self
    {
        $this->certificateCourse = $certificateCourse;

        return $this;
    }

    public function getRelevantAdditions(): ?string
    {
        return $this->relevantAdditions;
    }

    public function setRelevantAdditions(?string $relevantAdditions): self
    {
        $this->relevantAdditions = $relevantAdditions;

        return $this;
    }
}
