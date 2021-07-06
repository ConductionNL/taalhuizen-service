<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentBackgroundRepository;
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
 * @ORM\Entity(repositoryClass=StudentBackgroundRepository::class)
 */
class StudentBackground
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
     * @var String|null The way this student found the languageHouse.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice({"VOLUNTEER_CENTER", "LIBRARY_WEBSITE", "SOCIAL_MEDIA", "NEWSPAPER", "VIA_VIA", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $foundVia;

    /**
     * @var String|null The way this student found the languageHouse for if the OTHER option is selected.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $foundViaOther;

    /**
     * @var bool|null A boolean that is true if this student went to this languageHouse before.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $wentToLanguageHouseBefore;

    /**
     * @var String|null The reason why this student went to this languageHouse before.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $wentToLanguageHouseBeforeReason;

    /**
     * @var float|null The year this student went to this languageHouse before.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $wentToLanguageHouseBeforeYear;

    /**
     * @var array|null The network of this student.
     *
     * @Groups({"read", "write"})
     * @Assert\Choice(multiple=true, choices={"HOUSEHOLD_MEMBERS", "NEIGHBORS", "FAMILY_MEMBERS", "AID_WORKERS", "FRIENDS_ACQUAINTANCES", "PEOPLE_AT_MOSQUE_CHURCH", "ACQUAINTANCES_SPEAKING_OWN_LANGUAGE", "ACQUAINTANCES_SPEAKING_DUTCH"})
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $network = [];

    /**
     * @var int|null The place this student has on the participationLadder.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $participationLadder;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getFoundVia(): ?string
    {
        return $this->foundVia;
    }

    public function setFoundVia(?string $foundVia): self
    {
        $this->foundVia = $foundVia;

        return $this;
    }

    public function getFoundViaOther(): ?string
    {
        return $this->foundViaOther;
    }

    public function setFoundViaOther(?string $foundViaOther): self
    {
        $this->foundViaOther = $foundViaOther;

        return $this;
    }

    public function getWentToLanguageHouseBefore(): ?bool
    {
        return $this->wentToLanguageHouseBefore;
    }

    public function setWentToLanguageHouseBefore(?bool $wentToLanguageHouseBefore): self
    {
        $this->wentToLanguageHouseBefore = $wentToLanguageHouseBefore;

        return $this;
    }

    public function getWentToLanguageHouseBeforeReason(): ?string
    {
        return $this->wentToLanguageHouseBeforeReason;
    }

    public function setWentToLanguageHouseBeforeReason(?string $wentToLanguageHouseBeforeReason): self
    {
        $this->wentToLanguageHouseBeforeReason = $wentToLanguageHouseBeforeReason;

        return $this;
    }

    public function getWentToLanguageHouseBeforeYear(): ?float
    {
        return $this->wentToLanguageHouseBeforeYear;
    }

    public function setWentToLanguageHouseBeforeYear(?float $wentToLanguageHouseBeforeYear): self
    {
        $this->wentToLanguageHouseBeforeYear = $wentToLanguageHouseBeforeYear;

        return $this;
    }

    public function getNetwork(): ?array
    {
        return $this->network;
    }

    public function setNetwork(?array $network): self
    {
        $this->network = $network;

        return $this;
    }

    public function getParticipationLadder(): ?int
    {
        return $this->participationLadder;
    }

    public function setParticipationLadder(?int $participationLadder): self
    {
        $this->participationLadder = $participationLadder;

        return $this;
    }
}
