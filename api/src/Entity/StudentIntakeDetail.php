<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentAvailabilityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=StudentAvailabilityRepository::class)
 */
class StudentIntakeDetail
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
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $studentId;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $lastName;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $middleName;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $nickname;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $gender;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="datetime", length=255, nullable=true)
     */
    private ?\DateTime $dateOfBirth;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $streetAndHouseNumber;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $postalCode;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $place;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $phoneNumber;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $phoneNumberContactPerson;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $email;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="array", length=255, nullable=true)
     */
    private ?array $availability;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }

    public function getStudentId(): ?string
    {
        return $this->studentId;
    }

    public function setStudentId(string $studentId): self
    {
        $this->studentId = $studentId;

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

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(string $middleName): self
    {
        $this->middleName = $middleName;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTime
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(\DateTime $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getStreetAndHouseNumber(): ?string
    {
        return $this->streetAndHouseNumber;
    }

    public function setStreetAndHouseNumber(string $streetAndHouseNumber): self
    {
        $this->streetAndHouseNumber = $streetAndHouseNumber;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(string $place): self
    {
        $this->place = $place;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getPhoneNumberContactPerson(): ?string
    {
        return $this->phoneNumberContactPerson;
    }

    public function setPhoneNumberContactPerson(string $phoneNumberContactPerson): self
    {
        $this->phoneNumberContactPerson = $phoneNumberContactPerson;

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

    public function getAvailability(): ?array
    {
        return $this->getAvailability();
    }

    public function setAvailability(array $availability): self
    {
        $this->availability = $availability;

        return $this;
    }
}
