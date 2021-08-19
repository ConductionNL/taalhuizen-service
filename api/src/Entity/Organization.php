<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity Organization holds.
 *
 * The main entity associated with this DTO is the cc/Organization: https://taalhuizen-bisc.commonground.nu/api/v1/cc#tag/Organization.
 * DTO Organization exists of properties based on this contact catalogue entity, that is based on the following schema.org schema: https://schema.org/Organization.
 * Notable is that the addresses, emails and telephones properties have a OneToOne relation while there names are plural.
 * This is different than how this is done with the cc/Organization Entity, this is done (for now) because they should never contain more than one for this DTO.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get"={
 *              "read"=false
 *          },
 *          "get_user_roles"={
 *              "method"="GET",
 *              "path"="/organization/{uuid}/user_roles",
 *              "openapi_context" = {
 *                  "summary"="Get the user roles of this organization",
 *                  "description"="Get the user roles of this organization"
 *              }
 *          },
 *          "put"={
 *              "read"=false
 *          },
 *          "delete"={
 *              "read"=false
 *          },
 *     },
 *     collectionOperations={
 *          "get",
 *          "post",
 *     }
 * )
 * @ORM\Entity(repositoryClass=OrganizationRepository::class)
 */
class Organization
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
     * @var string Name of this organization
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="My company"
     *         }
     *     }
     * )
     */
    private string $name;

    /**
     * @var string|null Type of this organization. <br /> **When creating a Provider or LanguageHouse this is required!**
     *
     * @Assert\Choice({"Provider", "LanguageHouse"})
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"Provider", "LanguageHouse"},
     *             "example"="LanguageHouse"
     *         }
     *     }
     * )
     */
    private ?string $type;

    /**
     * @var Address|null Address of this organization
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Address::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @Assert\Valid
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Address $addresses = null;

    /**
     * @var Telephone|null Telephone of this organization
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Telephone::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @Assert\Valid
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Telephone $telephones = null;

    /**
     * @var Email|null Email of this organization
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Email::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @Assert\Valid
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Email $emails = null;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAddresses(): ?Address
    {
        return $this->addresses;
    }

    public function setAddresses(?Address $addresses): self
    {
        $this->addresses = $addresses;

        return $this;
    }

    public function getTelephones(): ?Telephone
    {
        return $this->telephones;
    }

    public function setTelephones(?Telephone $telephones): self
    {
        $this->telephones = $telephones;

        return $this;
    }

    public function getEmails(): ?Email
    {
        return $this->emails;
    }

    public function setEmails(?Email $emails): self
    {
        $this->emails = $emails;

        return $this;
    }
}
