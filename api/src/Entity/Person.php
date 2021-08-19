<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Repository\PersonRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity Person holds.
 *
 * The main entity associated with this DTO is the cc/Person: https://taalhuizen-bisc.commonground.nu/api/v1/cc#tag/Person.
 * DTO Person exists of properties based on this contact catalogue entity, that is based on the following schema.org schema: https://schema.org/Person.
 * The contactPreference and contactPreferenceOther properties differ from the schema.org/Person and are here because of jira issues like: https://lifely.atlassian.net/browse/BISC-76.
 * Notable is that the addresses and emails properties have a OneToOne relation while telephone has a OneToMany.
 * This is different than how this is done with the cc/Person Entity, this is done because (for now) only telephone should ever contain more than one for this DTO.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={"get"},
 *     collectionOperations={"get"}
 * )
 * @ORM\Entity(repositoryClass=PersonRepository::class)
 */
class Person
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
     * @var string Given name of this person
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="John"
     *         }
     *     }
     * )
     */
    private string $givenName;

    /**
     * @var string|null Additional name of this person
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="von"
     *         }
     *     }
     * )
     */
    private ?string $additionalName = null;

    /**
     * @var string|null Family name of this person
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Doe"
     *         }
     *     }
     * )
     */
    private ?string $familyName = null;

    /**
     * @var string|null Gender of this person
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"Male", "Female", "X"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"Male", "Female", "X"},
     *             "example"="Male"
     *         }
     *     }
     * )
     */
    private ?string $gender = null;

    /**
     * @var DateTime|null Birthday of this person
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="datetime", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="DateTime",
     *             "example"="12-02-1999"
     *         }
     *     }
     * )
     */
    private ?DateTime $birthday = null;

    /**
     * @var Address|null Address of this person
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
     * @var Collection|null Telephones of this person
     *
     * @Groups({"read", "write"})
     * @ORM\ManyToMany(targetEntity=Telephone::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @Assert\Valid
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Collection $telephones;

    /**
     * @var Email|null Email of this person
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Email::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @Assert\Valid
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Email $emails = null;

    /**
     * @var Organization|null Organization of this person
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Organization::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @Assert\Valid
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Organization $organization = null;

    /**
     * @var string|null The contact preference of the person.
     *
     * @example Whatsapp
     *
     * @Groups({"read","write"})
     * @Assert\Choice({"PHONECALL", "WHATSAPP", "EMAIL", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"PHONECALL", "WHATSAPP", "EMAIL", "OTHER"},
     *             "example"="PHONECALL"
     *         }
     *     }
     * )
     */
    private ?string $contactPreference = null;

    /**
     * @var string|null The contact preference of the person for when the OTHER option is selected.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Send contact person a message"
     *         }
     *     }
     * )
     */
    private ?string $contactPreferenceOther = null;

    public function __construct()
    {
//        $this->emails = new ArrayCollection();
        $this->telephones = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(string $givenName): self
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

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBirthday(): ?DateTime
    {
        return $this->birthday;
    }

    public function setBirthday(?DateTime $birthday): self
    {
        $this->birthday = $birthday;

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

    /**
     * @return Collection|Telephone[]
     */
    public function getTelephones()
    {
        return $this->telephones;
    }

    public function addTelephone(Telephone $telephone): self
    {
        if (!$this->telephones->contains($telephone)) {
            $this->telephones[] = $telephone;
        }

        return $this;
    }

    public function removeTelephone(Telephone $telephone): self
    {
        if ($this->telephones->contains($telephone)) {
            $this->telephones->removeElement($telephone);
        }

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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getContactPreference(): ?string
    {
        return $this->contactPreference;
    }

    public function setContactPreference(?string $contactPreference): self
    {
        $this->contactPreference = $contactPreference;

        return $this;
    }

    public function getContactPreferenceOther(): ?string
    {
        return $this->contactPreferenceOther;
    }

    public function setContactPreferenceOther(?string $contactPreferenceOther): self
    {
        $this->contactPreferenceOther = $contactPreferenceOther;

        return $this;
    }
}
