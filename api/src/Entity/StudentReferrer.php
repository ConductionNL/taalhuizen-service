<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentReferrerRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
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
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private UuidInterface $id;

    /**
     * @var String|null The StudentReferrer organization name.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"UWV", "SOCIAL_SERVICE", "LIBRARY", "WELFARE_WORK", "NEIGHBORHOOD_TEAM", "VOLUNTEER_ORGANIZATION", "LANGUAGE_PROVIDER", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"UWV", "SOCIAL_SERVICE", "LIBRARY", "WELFARE_WORK", "NEIGHBORHOOD_TEAM", "VOLUNTEER_ORGANIZATION", "LANGUAGE_PROVIDER", "OTHER"},
     *             "example"="UWV"
     *         }
     *     }
     * )
     */
    private ?string $referringOrganization;

    /**
     * @var String|null The StudentReferrer organization name when the OTHER option is selected.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $referringOrganizationOther;

    /**
     * @var String|null The email of this StudentReferrer.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $email;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
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
