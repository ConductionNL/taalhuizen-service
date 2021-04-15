<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\RegistrationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=RegistrationRepository::class)
 */
class Registration
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
     * @ORM\Column(type="string", length=255)
     */
    private $studentGivenName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $studentAdditionalName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $studentFamilyname;

    /**
     * @ORM\ManyToMany(targetEntity=Address::class)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $studentTelephone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $studentEmail;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $registrarOrganization;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $registrarName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $registrarEmail;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $registrarTelephone;

    /**
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $memo;

    public function __construct()
    {
        $this->address = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getRegistrarOrganization(): ?string
    {
        return $this->registrarOrganization;
    }

    public function setRegistrarOrganization(string $applicantOrganization): self
    {
        $this->registrarOrganization = $applicantOrganization;

        return $this;
    }

    public function getRegistrarName(): ?string
    {
        return $this->registrarName;
    }

    public function setRegistrarName(string $registrarName): self
    {
        $this->registrarName = $registrarName;

        return $this;
    }

    public function getRegistarAdditionalName(): ?string
    {
        return $this->registarAdditionalName;
    }

    public function getRegistrarEmail(): ?string
    {
        return $this->registrarEmail;
    }

    public function setRegistrarEmail(string $registrarEmail): self
    {
        $this->registrarEmail = $registrarEmail;

        return $this;
    }

    public function getRegistrarTelephone(): ?string
    {
        return $this->registrarTelephone;
    }

    public function setRegistrarTelephone(string $registrarTelephone): self
    {
        $this->registrarTelephone = $registrarTelephone;

        return $this;
    }

    public function getStudentGivenName(): ?string
    {
        return $this->studentGivenName;
    }

    public function setStudentGivenName(string $studentGivenName): self
    {
        $this->studentGivenName = $studentGivenName;

        return $this;
    }

    public function getStudentAdditionalName(): ?string
    {
        return $this->studentAdditionalName;
    }

    public function setStudentAdditionalName(?string $studentAdditionalName): self
    {
        $this->studentAdditionalName = $studentAdditionalName;

        return $this;
    }

    public function getStudentFamilyname(): ?string
    {
        return $this->studentFamilyname;
    }

    public function setStudentFamilyname(string $studentFamilyname): self
    {
        $this->studentFamilyname = $studentFamilyname;

        return $this;
    }

    public function getStudentTelephone(): ?string
    {
        return $this->studentTelephone;
    }

    public function setStudentTelephone(string $studentTelephone): self
    {
        $this->studentTelephone = $studentTelephone;

        return $this;
    }

    public function getStudentEmail(): ?string
    {
        return $this->studentEmail;
    }

    public function setStudentEmail(string $studentEmail): self
    {
        $this->studentEmail = $studentEmail;

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
