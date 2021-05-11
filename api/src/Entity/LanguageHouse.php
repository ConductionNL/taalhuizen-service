<?php

namespace App\Entity;

use App\Repository\LanguageHouseRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Resolver\LanguageHouseMutationResolver;
use App\Resolver\LanguageHouseQueryCollectionResolver;
use App\Resolver\LanguageHouseQueryItemResolver;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Inflector\Inflector;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     graphql={
 *          "item_query" = {
 *              "item_query" = LanguageHouseQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "userRolesBy" = {
 *              "collection_query" = LanguageHouseQueryCollectionResolver::class
 *          },
 *          "collection_query" = {
 *              "collection_query" = LanguageHouseQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = LanguageHouseMutationResolver::class,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = LanguageHouseMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = LanguageHouseMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     },
 *     attributes={
 *          "validation_groups" = {"Default", "remove"}
 *     }
 * )
 * @ApiFilter(SearchFilter::class, properties={"languageHouseId": "exact"})
 * @ORM\Entity(repositoryClass=LanguageHouseRepository::class)
 */
class LanguageHouse
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
     * @var string The Name of this Taalhuis.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $name;

    /**
     * @var array|null The address of this Taalhuis.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $address;

    /**
     * @var string The Telephone of this Provider.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phoneNumber;

    /**
     * @var string The Email of this Provider.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string Type LanguageHouse
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @var string The id of the cc/organization of a languageHouse.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $languageHouseId;

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

    public function getLanguageHouseId(): ?string
    {
        return $this->languageHouseId;
    }

    public function setLanguageHouseId(string $languageHouseId): self
    {
        $this->languageHouseId = $languageHouseId;

        return $this;
    }

}
