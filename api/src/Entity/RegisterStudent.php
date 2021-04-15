<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\RegisterStudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
* @ApiResource()
* @ORM\Entity(repositoryClass=RegistrationRepository::class)
*/
class RegisterStudent
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
 * @ORM\Column(type="string", length=255, nullable=true)
 */
private $givenName;

/**
 * @ORM\Column(type="string", length=255, nullable=true)
 */
private $additionalName;

/**
 * @ORM\Column(type="string", length=255, nullable=true)
 */
private $familyName;

/**
 * @ORM\Column(type="string", length=255, nullable=true)
 */
private $email;

/**
 * @ORM\Column(type="string", length=255, nullable=true)
 */
private $telephone;

public function __construct()
{
$this->address = new ArrayCollection();
}

public function getId(): Uuid
{
return $this->id;
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

public function getGivenName(): ?string
{
    return $this->givenName;
}

public function setGivenName(?string $givenName): self
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

public function setFamilyName(?string $familyName): self
{
    $this->familyName = $familyName;

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

public function getTelephone(): ?string
{
    return $this->telephone;
}

public function setTelephone(?string $telephone): self
{
    $this->telephone = $telephone;

    return $this;
}
}
