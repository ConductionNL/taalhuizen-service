<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentRepository;
use Doctrine\ORM\Mapping as ORM;
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

    public function getId(): ?int
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
}
