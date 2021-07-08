<?php

namespace App\Entity;

use App\Repository\OrganizationRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
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
     */
    private string $name;

    /**
     * @var Telephone|null Telephone of this organization
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Telephone::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Telephone $telephones;

    /**
     * @var Email|null Email of this organization
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Email::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Email $emails;

    /**
     * @var string|null Type of this organization
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $type;

    /**
     * @var Address|null Address of this organization
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Address::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Address $addresses;

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
