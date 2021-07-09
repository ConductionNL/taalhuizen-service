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
 * All properties that the DTO entity Report holds.
 *
 * DTO Report exists of properties based on the following jira epics: https://lifely.atlassian.net/browse/BISC-173 and https://lifely.atlassian.net/browse/BISC-179.
 * Notable is that there are no providerId or LanguageHouseId properties present in this Entity. This is because custom endpoint can be used for this purpose.
 * Besides that, the property base64 was renamed from base64data to base64. This was mostly done for consistency and cleaner names.
 *
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
