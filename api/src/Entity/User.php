<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
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
 *              "openapi_context" = {
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
 *              "openapi_context" = {
 *                  "summary"="Login a user with a username and password.",
 *                  "description"="Login a user with a username and password.",
 *                  "requestBody" = {
 *                      "content" = {
 *                          "application/json" = {
 *                              "schema" = {
 *                                  "type" = "object",
 *                                  "properties" =
 *                                      {
 *                                          "username" = {"type" = "string"},
 *                                          "password" = {"type" = "string"},
 *                                      },
 *                              },
 *                              "example" = {
 *                                  "username" = "JohnDoe@gmail.com",
 *                                  "password" = "n$5Ssqs]eCDT!$})",
 *                              },
 *                          },
 *                      },
 *                  },
 *                  "responses" = {
 *                      "201" = {
 *                          "description" = "Created",
 *                          "content" = {
 *                              "application/json" = {
 *                                  "schema" = {
 *                                      "type" = "object",
 *                                      "properties" =
 *                                          {
 *                                              "token" = {"type" = "string"},
 *                                          },
 *                                  },
 *                                  "example" = {
 *                                      "token" = "eyJhbGciOiJSUzUxMiJ9.eyJ1c2VySWQiOiI2ZWZjYzNkYy0wN2IzLTQ5YzQtOGU1ZS1jOGIwMTQyYTk4ODYiLCJ0eXBlIjoibG9naW4iLCJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3QiLCJpYXMiOjE2MjczMDk0NDMsImV4cCI6MTYyODE3MzQ0M30.aA3rHw69mzIF2ycf36eD1DhLcnZtFtKK6r7ymajI0OTx7FvKJal7XR_sxfx-Adefps3RTc_VDAw8VOcFtl6S3P7tWgXPJCnZ2DchAlZTBAUhym0SqApW6mlouZLjBRuxL_rsz6kAHcFMWWQYyfN5jzRO1A_Qyo53IF2bs_EdiebXe-TKrAk_FTovcbxgFnznQ-P3l1IDe9v9Q3RrWfwUVI-i97pCOTvh77RtuHtvT-6mO4dW-GuKQOmYHQVijNgFVf3vuQiZk3kNeQi33jdwl0ij6e9PtVupSmroBEpZ0-SOJwv0aEfQSm3ZzLuA3gBbjXM29Evv4RobaQsT7XbSzCqkdY_VbqD4OvoQzWTuRrjufYOld1m6eFJ5-jducJBVf14QJiUkrUa0iz3IOAgcBmsMdaZOYw9IBLbJYCjzKl0SluNr0ltncySY2E3Qk8KOv7ZOoxmzjzSbrr39USgUjTePEgKXKNtU4q-363SL6cs5PD51lo2obFOZ4E-Eo12SPryCmrhixIFgQSqEKFTaOIOy1fQLd-KF8XEs-K9Op_W01sLYXA1TeW6vszHg8Lv9HtzKpoh31-Tj-hHTzpMUYHIv0Kj6y4DWUJRkAGmjdJAnmxwNy3B_WhFxYnqvcFWMi0W3d-lBx3PFFJCa3lMPHXYfBpvBBHzKzb3sFGiyZCs"
 *                                  },
 *                              },
 *                          },
 *                      },
 *                  },
 *              },
 *          },
 *          "logout"={
 *              "method"="POST",
 *              "path"="/users/logout",
 *              "openapi_context" = {
 *                  "summary"="Logout the currently logged in user.",
 *                  "description"="Logout the currently logged in user."
 *              }
 *          },
 *          "request_password_reset"={
 *              "method"="POST",
 *              "path"="/users/request_password_reset",
 *              "openapi_context" = {
 *                  "summary"="Request a password reset token for a user.",
 *                  "description"="Request a password reset token for a user."
 *              }
 *          },
 *          "reset_password"={
 *              "method"="POST",
 *              "path"="/users/reset_password",
 *              "openapi_context" = {
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
     * @var string|null The Username of this User.
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
    private ?string $username = null;

    /**
     * @var Person|null A contact component person of this User.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private ?Person $person = null;

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
    private ?string $userEnvironment = null;

    /**
     * @var string|null A contact component organization id of this User.
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
    private ?string $organizationId = null;

    /**
     * @var string|null The organization name of this User.
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
    private ?string $organizationName = null;

    /**
     * @var string|null The Password of this User.
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
    private ?string $password = null;

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
    private ?string $token = null;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): self
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
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
