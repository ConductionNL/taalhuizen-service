<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentBackgroundRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource()
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $foundVia;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $foundViaOther;

    // Renamed from wentToTaalhuisBefore to wentToLanguageHouseBefore. todo: EAV variable might need this rename as well?
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $wentToLanguageHouseBefore;

    // Renamed from wentToTaalhuisBeforeReason to wentToLanguageHouseBeforeReason. todo: EAV variable might need this rename as well?
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $wentToLanguageHouseBeforeReason;

    // Renamed from $wentToTaalhuisBeforeYear to $wentToLanguageHouseBeforeYear. todo: EAV variable might need this rename as well?
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $wentToLanguageHouseBeforeYear;

    // todo: Needs to be an array of enum options: not tested if this works and if this is the correct way to do this
    /**
     * @Assert\Choice(multiple=true, choices={"HOUSEHOLD_MEMBERS", "NEIGHBORS", "FAMILY_MEMBERS", "AID_WORKERS", "FRIENDS_ACQUAINTANCES", "PEOPLE_AT_MOSQUE_CHURCH", "ACQUAINTANCES_SPEAKING_OWN_LANGUAGE", "ACQUAINTANCES_SPEAKING_DUTCH"})
     * @ORM\Column(type="array", nullable=true)
     */
    private array $network = [];

    /**
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
