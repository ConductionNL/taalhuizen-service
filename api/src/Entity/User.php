<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use App\Resolver\UserMutationResolver;
use App\Resolver\UserQueryCollectionResolver;
use App\Resolver\UserQueryItemResolver;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity User holds.
 *
 * The main entity associated with this DTO is the uc/User: https://taalhuizen-bisc.commonground.nu/api/v1/uc#tag/User.
 * DTO User exists of at least a username, a password and a Person.
 * And can also have a organization (id and/or name), a userEnvironment, 0 or more userRoles and a password reset token.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "get_current_user"={
 *              "method"="GET",
 *              "path"="/users/current_user",
 *              "swagger_context" = {
 *                  "summary"="Get the current user.",
 *                  "description"="Get the current user."
 *              }
 *          },
 *          "put",
 *          "delete"
 *     },
 *     collectionOperations={
 *          "get",
 *          "post",
 *          "login"={
 *              "method"="POST",
 *              "path"="/users/login",
 *              "swagger_context" = {
 *                  "summary"="Login a user with a username and password.",
 *                  "description"="Login a user with a username and password."
 *              }
 *          },
 *          "logout"={
 *              "method"="POST",
 *              "path"="/users/logout",
 *              "swagger_context" = {
 *                  "summary"="Logout the currently logged in user.",
 *                  "description"="Logout the currently logged in user."
 *              }
 *          },
 *          "request_password_reset"={
 *              "method"="POST",
 *              "path"="/users/request_password_reset",
 *              "swagger_context" = {
 *                  "summary"="Request a password reset token for a User.",
 *                  "description"="Request a password reset token for a User."
 *              }
 *          },
 *          "reset_password"={
 *              "method"="POST",
 *              "path"="/users/reset_password",
 *              "swagger_context" = {
 *                  "summary"="Reset the password of a User with a token.",
 *                  "description"="Reset the password of a User with a token."
 *              }
 *          },
 *     })
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User
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
     * @var string The Username of this User.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="JohnDoe@gmail.com"
     *         }
     *     }
     * )
     */
    private string $username;

    /**
     * @var Person A contact component person of this User.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private Person $person;

    /**
     * @var string|null The userEnvironment of this User.
     *
     * @Assert\Choice(
     *      {"BISC","TAALHUIS","AANBIEDER"}
     * )
     * @Groups({"read", "write"})
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"BISC","TAALHUIS","AANBIEDER"},
     *             "example"="BISC"
     *         }
     *     }
     * )
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $userEnvironment;

    /**
     * @var String|null A contact component organization id of this User.
     *
     * @Groups({"read", "write"})
     * @Assert\Length(min=36, max=36)
     * @ORM\Column(type="string", length=36, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="e2984465-190a-4562-829e-a8cca81aa35d"
     *         }
     *     }
     * )
     */
    private ?string $organizationId;

    /**
     * @var String|null The organization name of this User.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="Taalhuis X"
     *         }
     *     }
     * )
     */
    private ?string $organizationName;

    /**
     * @var string The Password of this User.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="n$5Ssqs]eCDT!$})"
     *         }
     *     }
     * )
     */
    private string $password;

    /**
     * @var string|null The Token for password reset.
     *
     * @Assert\Length(
     *     max = 2550
     * )
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOiJlMjk4NDQ2NS0xOTBhLTQ1NjItODI5ZS1hOGNjYTgxYWEzNWQiLCJlbWFpbCI6ImpvaG5Eb2VAZ21haWwuY29tIiwiaXNzIjoiaXNzIiwiaWFzIjoiaWFzIiwiZXhwIjoiZXhwIn0.dBLCHRmqFyTv3tiyI0mpYnlcQ0UTRqG9JpKw5zd0I2U"
     *         }
     *     }
     * )
     */
    private ?string $token;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getUserEnvironment(): ?string
    {
        return $this->userEnvironment;
    }

    public function setUserEnvironment(?string $userEnvironment): self
    {
        $this->userEnvironment = $userEnvironment;

        return $this;
    }

    public function getOrganizationId(): ?string
    {
        return $this->organizationId;
    }

    public function setOrganizationId(?string $organizationId): self
    {
        $this->organizationId = $organizationId;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(?string $organizationName): self
    {
        $this->organizationName = $organizationName;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }
}
