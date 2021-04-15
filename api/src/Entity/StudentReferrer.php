<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentReferrerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=StudentReferrerRepository::class)
 */
class StudentReferrer
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
    private $referringOrganization;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $referringOrganizationOther;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferringOrganization(): ?string
    {
        return $this->referringOrganization;
    }

    public function setReferringOrganization(?string $referringOrganization): self
    {
        $this->referringOrganization = $referringOrganization;

        return $this;
    }

    public function getReferringOrganizationOther(): ?string
    {
        return $this->referringOrganizationOther;
    }

    public function setReferringOrganizationOther(?string $referringOrganizationOther): self
    {
        $this->referringOrganizationOther = $referringOrganizationOther;

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
}
