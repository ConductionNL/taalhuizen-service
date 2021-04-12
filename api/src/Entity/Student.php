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
     * @var string (CIR) "Nee, omdat", "Ja" or "Volgt momenteel inburgering"
     *
     * @Assert\Choice({"Nee, omdat", "Ja", "Volgt momenteel inburgering"})
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $civicIntegrationRequirement;

    /**
     * @var string (CIR = civicIntegrationRequirement)
     *
     * @Assert\Choice({"afgerond", "afkomstig uit EU land", "vanwege vrijstelling of Zroute"})
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $CIRNo;

    /**
     * @var Datetime (CIR = civicIntegrationRequirement)
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $CIRCompletionDate;

    /**
     * @var string Family name of this student
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $familyName;

    /**
     * @var string Additional name of this student
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $additionalName;

    /**
     * @var string Given name of this student
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     */
    private $givenName;

    /**
     * @var string Gender of this student. **Male**, **Female**, **X**
     *
     * @example Male
     *
     * @Assert\Choice(
     *      {"Male","Female","X"}
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gender;

    /**
     * @var string Date of birth of this Student.
     *
     * @example 15-03-2000
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $birthday;

    /**
     * @var Address The address of this Student.
     *
     * @MaxDepth(1)
     * @Groups({"read", "write"})
     * @ORM\ManyToMany(targetEntity=Address::class, inversedBy="students")
     */
    private $address;


    /**
     * @var string Telephone of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telephone;

    /**
     * @var string Email of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string Contact Telephone of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactTelephone;

    /**
     * @var string Contact Preference of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactPreference;

    /**
     * @var string Country of Origin of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $countryOfOrigin;

    /**
     * @var string Native language of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nativeLanguage;

    /**
     * @var string Other speaking languages of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $otherLanguages;

    /**
     * @var string The family composition of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $familyComposition;

    /**
     * @var string Number of children of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numberOfChildren;

    /**
     * @var string The children's date of Birth of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dateOfBirthChildren;

    /**
     * @var string This student is referred by. **UWV**, **Sociale dienst**, **Bibliotheek**, **Welzijnswerk**, **Buurt/dorpsteam**, **Vrijwilligersorganisatie**, **Taalaanbieder**, **Anders nl:**
     *
     * @example UWV
     *
     * @Assert\Choice(
     *     {"UWV", "Sociale dienst", "Bibliotheek", "Welzijnswerk", "Buurt/dorpsteam", "Vrijwilligersorganisatie", "Taalaanbieder", "Anders nl:"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $referredBy;

    /**
     * @var string Email of the referred by
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $referredByEmail;

    /**
     * @var string Contact network of the student
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactWith;

    /**
     * @var string Participation ladder of the student
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $participationLadder;

    /**
     * @var string Language level of this Student. **NT1**, **NT2**
     *
     * @example NT1
     *
     * @Assert\Choice(
     *      {"NT1","NT2"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $languageLevel;

    /**
     * @var DateTime This student is in the Netherlands since
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $inNetherlandSince;

    /**
     * @var string preferred language of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $preferredLanguage;

    /**
     * @var boolean latin Alphabet of this student.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $latinAlphabet;

    /**
     * @var string measured language level of this Student. **A0**, **A1**, **A2**, **B1**, **B2**, **C1**, **C2**, **Unknown**
     *
     * @example A0
     *
     * @Assert\Choice(
     *      {"A0","A1","A2","B1","B2","C1","C2","Unknown"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $measuredLanguageLevel;

    /**
     * @var string speaking language level of this Student. **beginner**, **reasonable**, **advanced**
     *
     * @example beginner
     *
     * @Assert\Choice(
     *      {"beginner","reasonable","advanced"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $speakingLanguageLevel;

    /**
     * @var string last education level of this Student. **no education**, **several years po**, **po**, **vo**, **mbo**, **hbo**, **university**
     *
     * @example mbo
     *
     * @Assert\Choice(
     *      {"no education","several years po","po","vo","mbo","hbo","university"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastEducationLevel;

    /**
     * @var boolean degreeGrantedStatus of this Student.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $degreeGrantedStatus;

    /**
     * @var string current education of this Student. **yes**, **no**, **no, but followed**
     *
     * @example yes
     *
     * @Assert\Choice(
     *      {"yes","no","no, but followed"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentEducation;

    /**
     * @var Datetime StartDate Education of this Student.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startEducation;


    /**
     * @var Datetime Enddate Education of this Student.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endEducation;

    /**
     * @var string education institution name of this Student.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $educationInstitutionName;

    /**
     * @var boolean diploma or certificate.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $diplomaOrCertificate;

    /**
     * @var string The Isced Education Level Code of this Student.
     *
     * @example HBO
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $iscedEducationLevelCode;

    /**
     * @var boolean The current course of this Student.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $currentCourse;

    /**
     * @var string Course institution name of this Student.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $courseInstitutionName;

    /**
     * @var string The type of teacher of this Student. **Professional**, **Volunteer**, **Both**
     *
     * @example Professional
     *
     * @Assert\Choice(
     *      {"Professional","Volunteer","Both"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $typeOfTeacher;

    /**
     * @var string The type of teacher of this Student. **Individual**, **Group**
     *
     * @example Individual
     *
     * @Assert\Choice(
     *      {"Individual","Group"}
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $typeOfCourse;

    /**
     * @var int Amount of hours of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private $amountOfHours;

    /**
     * @var boolean The course certificate of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $certificateCourse;

    /**
     * @var string Trained for work of this Student.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $trainedForWork;

    /**
     * @var string Last work place of this Student.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastWorkPlace;

    /**
     * @var string The daytime activities of this Student.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $daytimeActivities;

    /**
     * @var array The tings this Student wants to learn.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="array", nullable=true)
     */
    private $wantToLearn = [];

    /**
     * @var string This Student done this before.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $doneThis;

    /**
     * @var string Why yes or no of this Student.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $whyYesOrNo;

    /**
     * @var string Why this of this Student.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $whyThis;

    /**
     * @var string Why now of this Student.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $whyNow;

    /**
     * @var array how this Student wants to learn.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="array", nullable=true)
     */
    private $howToLearn = [];

    /**
     * @var string Comment of this Student.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $comment;

    /**
     * @var array The Availability of this Student.
     *
     * @example An array of strings with the abbreviation of the day and a time slot, for example; mon morning, mon afternoon, mon evening
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="array", nullable=true)
     */
    private $availability = [];

    /**
     * @var string The Availability Note of this Student.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $availabilityNote;

    /**
     * @var string The reading test result of this Student.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $readTestResult;

    /**
     * @var string The writing test result of this Student.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $writingTestResult;

    /**
     * @var boolean The consent form of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private $consentForm;

    /**
     * @var boolean The consent data of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private $consentData;

    /**
     * @var boolean The consent basic data of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private $consentBasicData;

    /**
     * @var boolean The consent test of this Student.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean")
     */
    private $consentTest;

    public function __construct()
    {
        $this->address = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCivicIntegrationRequirementNo(): ?string
    {
        return $this->civicIntegrationRequirementNo;
    }

    public function setCivicIntegrationRequirementNo(?string $civicIntegrationRequirementNo): self
    {
        $this->civicIntegrationRequirementNo = $civicIntegrationRequirementNo;

        return $this;
    }

    public function getCivicIntegrationRequirementCompletionDate(): ?\DateTimeInterface
    {
        return $this->civicIntegrationRequirementCompletionDate;
    }

    public function setCivicIntegrationRequirementCompletionDate(?\DateTimeInterface $civicIntegrationRequirementCompletionDate): self
    {
        $this->civicIntegrationRequirementCompletionDate = $civicIntegrationRequirementCompletionDate;

        return $this;
    }

    public function getCivicIntegrationRequirement(): ?string
    {
        return $this->civicIntegrationRequirement;
    }

    public function setCivicIntegrationRequirement(?string $civicIntegrationRequirement): self
    {
        $this->civicIntegrationRequirement = $civicIntegrationRequirement;

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

    public function getAdditionalName(): ?string
    {
        return $this->additionalName;
    }

    public function setAdditionalName(?string $additionalName): self
    {
        $this->additionalName = $additionalName;

        return $this;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(string $givenName): self
    {
        $this->givenName = $givenName;

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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

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

    public function getCountryOfOrigin(): ?string
    {
        return $this->countryOfOrigin;
    }

    public function setCountryOfOrigin(?string $countryOfOrigin): self
    {
        $this->countryOfOrigin = $countryOfOrigin;

        return $this;
    }

    public function getNativeLanguage(): ?string
    {
        return $this->nativeLanguage;
    }

    public function setNativeLanguage(?string $nativeLanguage): self
    {
        $this->nativeLanguage = $nativeLanguage;

        return $this;
    }

    public function getOtherLanguages(): ?string
    {
        return $this->otherLanguages;
    }

    public function setOtherLanguages(?string $otherLanguages): self
    {
        $this->otherLanguages = $otherLanguages;

        return $this;
    }

    public function getFamilyComposition(): ?string
    {
        return $this->familyComposition;
    }

    public function setFamilyComposition(?string $familyComposition): self
    {
        $this->familyComposition = $familyComposition;

        return $this;
    }

    public function getNumberOfChildren(): ?string
    {
        return $this->numberOfChildren;
    }

    public function setNumberOfChildren(?string $numberOfChildren): self
    {
        $this->numberOfChildren = $numberOfChildren;

        return $this;
    }

    public function getDateOfBirthChildren(): ?string
    {
        return $this->dateOfBirthChildren;
    }

    public function setDateOfBirthChildren(?string $dateOfBirthChildren): self
    {
        $this->dateOfBirthChildren = $dateOfBirthChildren;

        return $this;
    }

    public function getReferredBy(): ?string
    {
        return $this->referredBy;
    }

    public function setReferredBy(?string $referredBy): self
    {
        $this->referredBy = $referredBy;

        return $this;
    }

    public function getReferredByEmail(): ?string
    {
        return $this->referredByEmail;
    }

    public function setReferredByEmail(?string $referredByEmail): self
    {
        $this->referredByEmail = $referredByEmail;

        return $this;
    }

    public function getContactWith(): ?string
    {
        return $this->contactWith;
    }

    public function setContactWith(?string $contactWith): self
    {
        $this->contactWith = $contactWith;

        return $this;
    }

    public function getParticipationLadder(): ?string
    {
        return $this->participationLadder;
    }

    public function setParticipationLadder(?string $participationLadder): self
    {
        $this->participationLadder = $participationLadder;

        return $this;
    }

    public function getLanguageLevel(): ?string
    {
        return $this->languageLevel;
    }

    public function setLanguageLevel(?string $languageLevel): self
    {
        $this->languageLevel = $languageLevel;

        return $this;
    }

    public function getInNetherlandSince(): ?\DateTimeInterface
    {
        return $this->inNetherlandSince;
    }

    public function setInNetherlandSince(?\DateTimeInterface $inNetherlandSince): self
    {
        $this->inNetherlandSince = $inNetherlandSince;

        return $this;
    }

    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(?string $preferredLanguage): self
    {
        $this->preferredLanguage = $preferredLanguage;

        return $this;
    }

    public function getLatinAlphabet(): ?bool
    {
        return $this->latinAlphabet;
    }

    public function setLatinAlphabet(?bool $latinAlphabet): self
    {
        $this->latinAlphabet = $latinAlphabet;

        return $this;
    }

    public function getMeasuredLanguageLevel(): ?string
    {
        return $this->measuredLanguageLevel;
    }

    public function setMeasuredLanguageLevel(?string $measuredLanguageLevel): self
    {
        $this->measuredLanguageLevel = $measuredLanguageLevel;

        return $this;
    }

    public function getSpeakingLanguageLevel(): ?string
    {
        return $this->speakingLanguageLevel;
    }

    public function setSpeakingLanguageLevel(?string $speakingLanguageLevel): self
    {
        $this->speakingLanguageLevel = $speakingLanguageLevel;

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

    public function getDegreeGrantedStatus(): ?bool
    {
        return $this->degreeGrantedStatus;
    }

    public function setDegreeGrantedStatus(?bool $degreeGrantedStatus): self
    {
        $this->degreeGrantedStatus = $degreeGrantedStatus;

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

    public function getLastEducationLevel(): ?string
    {
        return $this->lastEducationLevel;
    }

    public function setLastEducationLevel(?string $lastEducationLevel): self
    {
        $this->lastEducationLevel = $lastEducationLevel;

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

    public function getEducationInstitutionName(): ?string
    {
        return $this->educationInstitutionName;
    }

    public function setEducationInstitutionName(?string $educationInstitutionName): self
    {
        $this->educationInstitutionName = $educationInstitutionName;

        return $this;
    }

    public function getDiplomaOrCertificate(): ?bool
    {
        return $this->diplomaOrCertificate;
    }

    public function setDiplomaOrCertificate(?bool $diplomaOrCertificate): self
    {
        $this->diplomaOrCertificate = $diplomaOrCertificate;

        return $this;
    }

    public function getCurrentCourse(): ?bool
    {
        return $this->currentCourse;
    }

    public function setCurrentCourse(?bool $currentCourse): self
    {
        $this->currentCourse = $currentCourse;

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

    public function getAmountOfHours(): ?int
    {
        return $this->amountOfHours;
    }

    public function setAmountOfHours(?int $amountOfHours): self
    {
        $this->amountOfHours = $amountOfHours;

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

    public function getTrainedForWork(): ?string
    {
        return $this->trainedForWork;
    }

    public function setTrainedForWork(?string $trainedForWork): self
    {
        $this->trainedForWork = $trainedForWork;

        return $this;
    }

    public function getLastWorkPlace(): ?string
    {
        return $this->lastWorkPlace;
    }

    public function setLastWorkPlace(?string $lastWorkPlace): self
    {
        $this->lastWorkPlace = $lastWorkPlace;

        return $this;
    }

    public function getDaytimeActivities(): ?string
    {
        return $this->daytimeActivities;
    }

    public function setDaytimeActivities(?string $daytimeActivities): self
    {
        $this->daytimeActivities = $daytimeActivities;

        return $this;
    }

    public function getWantToLearn(): ?array
    {
        return $this->wantToLearn;
    }

    public function setWantToLearn(?array $wantToLearn): self
    {
        $this->wantToLearn = $wantToLearn;

        return $this;
    }

    public function getDoneThis(): ?bool
    {
        return $this->doneThis;
    }

    public function setDoneThis(?bool $doneThis): self
    {
        $this->doneThis = $doneThis;

        return $this;
    }

    public function getWhyYesOrNo(): ?string
    {
        return $this->whyYesOrNo;
    }

    public function setWhyYesOrNo(?string $whyYesOrNo): self
    {
        $this->whyYesOrNo = $whyYesOrNo;

        return $this;
    }

    public function getWhyThis(): ?string
    {
        return $this->whyThis;
    }

    public function setWhyThis(?string $whyThis): self
    {
        $this->whyThis = $whyThis;

        return $this;
    }

    public function getWhyNow(): ?string
    {
        return $this->whyNow;
    }

    public function setWhyNow(?string $whyNow): self
    {
        $this->whyNow = $whyNow;

        return $this;
    }

    public function getHowToLearn(): ?array
    {
        return $this->howToLearn;
    }

    public function setHowToLearn(?array $howToLearn): self
    {
        $this->howToLearn = $howToLearn;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

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

    public function getAvailabilityNote(): ?string
    {
        return $this->availabilityNote;
    }

    public function setAvailabilityNote(?string $availabilityNote): self
    {
        $this->availabilityNote = $availabilityNote;

        return $this;
    }

    public function getReadTestResult(): ?string
    {
        return $this->readTestResult;
    }

    public function setReadTestResult(?string $readTestResult): self
    {
        $this->readTestResult = $readTestResult;

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

    public function getConsentForm(): ?bool
    {
        return $this->consentForm;
    }

    public function setConsentForm(bool $consentForm): self
    {
        $this->consentForm = $consentForm;

        return $this;
    }

    public function getConsentData(): ?bool
    {
        return $this->consentData;
    }

    public function setConsentData(bool $consentData): self
    {
        $this->consentData = $consentData;

        return $this;
    }

    public function getConsentBasicData(): ?bool
    {
        return $this->consentBasicData;
    }

    public function setConsentBasicData(bool $consentBasicData): self
    {
        $this->consentBasicData = $consentBasicData;

        return $this;
    }

    public function getConsentTest(): ?bool
    {
        return $this->consentTest;
    }

    public function setConsentTest(bool $consentTest): self
    {
        $this->consentTest = $consentTest;

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

}
