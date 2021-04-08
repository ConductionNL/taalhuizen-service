<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $civicIntegrationRequirement;

    /**
     * @var string (CIR = civicIntegrationRequirement)
     *
     * @Assert\Choice({"afgerond", "afkomstig uit EU land", "vanwege vrijstelling of Zroute"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $CIRNo;

    /**
     * @var Datetime (CIR = civicIntegrationRequirement)
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $CIRCompletionDate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prefixName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @Assert\Choice({"Man", "Vrouw", "X"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $birthday;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $houseNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $houseNumberSuffix;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $postalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $locality;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telephone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactTelephone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $contactPreference;

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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

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
}
