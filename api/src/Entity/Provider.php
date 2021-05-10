<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\ProviderRepository;
use App\Resolver\ProviderMutationResolver;
use App\Resolver\ProviderQueryCollectionResolver;
use App\Resolver\ProviderQueryItemResolver;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *      graphql={
 *          "item_query" = {
 *              "item_query" = ProviderQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "userRolesBy" = {
 *              "item_query" = ProviderQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = ProviderQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = ProviderMutationResolver::class,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = ProviderMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = ProviderMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     },
 * )
 *
 * @ORM\Entity(repositoryClass=ProviderRepository::class)
 */
class Provider
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
     * @var string The Name of this Provider.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string The Telephone of this Provider.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phoneNumber;

    /**
     * @var string The Email of this Provider.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var array|null The address of this Aanbieder.
     *
     * @Groups({"write"})
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $address = [];

    /**
     * @var string Type Aanbieder
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @var array|null The userRoles of this Taalhuis.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $userRoles;

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
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

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

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

    public function getAddress(): ?array
    {
        return $this->address;
    }

    public function setAddress(?array $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUserRoleType(): ?array
    {
        return $this->userRoles;
    }

    public function setUserRoleType(array $userRoles): self
    {
        $this->userRoles = $userRoles;

        return $this;
    }


}
