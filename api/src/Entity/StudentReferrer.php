<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentReferrerRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
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
    private UuidInterface $id;

    /**
     * @Assert\Choice({"UWV", "SOCIAL_SERVICE", "LIBRARY", "WELFARE_WORK", "NEIGHBORHOOD_TEAM", "VOLUNTEER_ORGANIZATION", "LANGUAGE_PROVIDER", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $referringOrganization;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $referringOrganizationOther;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $email;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
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
