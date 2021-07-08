<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ReportRepository;
use App\Resolver\ReportMutationResolver;
use App\Resolver\ReportQueryCollectionResolver;
use App\Resolver\ReportQueryItemResolver;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=ReportRepository::class)
 */
class Report
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
     * @var string|null The language house the report applies to.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $languageHouseId;

    /**
     * @var string|null The provider this report applies to.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $providerId;

    /**
     * @var string|null A date from which you want data in the report.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $dateFrom;

    /**
     * @var string|null A date until which you want data in the report.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $dateUntil;

    /**
     * @var string|null The filename of the report.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $filename;

    // Renamed from base64data to base64.
    /**
     * @var string|null A base64 encoded string containing the file's contents.
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $base64;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getLanguageHouseId(): ?string
    {
        return $this->languageHouseId;
    }

    public function setLanguageHouseId(?string $languageHouseId): self
    {
        $this->languageHouseId = $languageHouseId;

        return $this;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(?string $providerId): self
    {
        $this->providerId = $providerId;

        return $this;
    }

    public function getDateFrom(): ?string
    {
        return $this->dateFrom;
    }

    public function setDateFrom(?string $dateFrom): self
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function getDateUntil(): ?string
    {
        return $this->dateUntil;
    }

    public function setDateUntil(?string $dateUntil): self
    {
        $this->dateUntil = $dateUntil;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getBase64(): ?string
    {
        return $this->base64;
    }

    public function setBase64(?string $base64): self
    {
        $this->base64 = $base64;

        return $this;
    }
}
