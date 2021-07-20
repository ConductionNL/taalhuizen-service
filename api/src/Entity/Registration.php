<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity Registration holds.
 *
 * The main entity associated with this DTO is the edu/Participant: https://taalhuizen-bisc.commonground.nu/api/v1/edu#tag/Participant.
 * DTO Registration exists of properties based on the following jira epics: https://lifely.atlassian.net/browse/BISC-59 and https://lifely.atlassian.net/browse/BISC-121.
 * And mainly the following issue: https://lifely.atlassian.net/browse/BISC-166.
 * The student and registrar input fields match the Person Entity, that is why there are two Person objects used here instead of matching the exact properties in the graphql schema.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "put",
 *          "delete"
 *     },
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=RegistrationRepository::class)
 */
class Registration
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
     * @var string A language house for this registration.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="497f6eca-6276-4993-bfeb-53cbbbba6f08"
     *         }
     *     }
     * )
     */
    private string $languageHouseId;

    /**
     * @var Person A contact catalogue person for the student. <br /> **This person must contain an Email and Telephone!**
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private Person $student;

    /**
     * @var Person A contact catalogue person for the registrar. <br /> **This person must contain an Organization!**
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Person::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @MaxDepth(1)
     */
    private Person $registrar;

    /**
     * @var string|null A note for/with this registration.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=2550, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Explanation of the registration"
     *         }
     *     }
     * )
     */
    private ?string $memo;

    /**
     * @var string|null The Status of this registration.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"Pending", "Accepted"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"Pending", "Accepted"},
     *             "example"="Pending"
     *         }
     *     }
     * )
     */
    private ?string $status = 'Pending';

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getLanguageHouseId(): string
    {
        return $this->languageHouseId;
    }

    public function setLanguageHouseId(string $languageHouseId): self
    {
        $this->languageHouseId = $languageHouseId;

        return $this;
    }

    public function getStudent(): Person
    {
        return $this->student;
    }

    public function setStudent(Person $student): self
    {
        $this->student = $student;

        return $this;
    }

    public function getRegistrar(): Person
    {
        return $this->registrar;
    }

    public function setRegistrar(Person $registrar): self
    {
        $this->registrar = $registrar;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
