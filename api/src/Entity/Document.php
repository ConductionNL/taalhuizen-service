<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity Document holds.
 *
 * The main entity associated with this DTO is the wrc/Document: https://taalhuizen-bisc.commonground.nu/api/v1/wrc#tag/Document.
 * DTO Document exists of a properties based on this web resource catalogue entity.
 * But the other main source this Document entity is based on, are the following jira epics: https://lifely.atlassian.net/browse/BISC-65, https://lifely.atlassian.net/browse/BISC-116 and https://lifely.atlassian.net/browse/BISC-120.
 * Notable is that there are no studentId or providerEmployeeId properties present in this Entity. This is because custom endpoint can be used for this purpose.
 * Besides that, the property base64 was renamed from base64data to base64. This name changes was mostly done for consistency and a cleaner name.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "delete"
 *     },
 *     collectionOperations={
 *          "get",
 *          "post",
 *          "post_download"={
 *              "method"="POST",
 *              "path"="/documents/{uuid}/download",
 *              "openapi_context" = {
 *                  "summary"="Download a document",
 *                  "description"="Download a document"
 *              }
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass=DocumentRepository::class)
 */
class Document
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
     * @var string Filename of this document.
     *
     * @Assert\Length(
     *     max = 255
     *)
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Document X"
     *         }
     *     }
     * )
     */
    private string $filename;

    /**
     * @var string Base64 of this document.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="text")
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="base64"
     *         }
     *     }
     * )
     */
    private string $base64;

    /**
     * @var ?string Student id of this document.
     *
     * @Groups({"read", "write"})
     * @Assert\Length(min=36, max=36)
     * @ORM\Column(type="string", length=36)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="e2984465-190a-4562-829e-a8cca81aa35d"
     *         }
     *     }
     * )
     */
    private ?string $studentId;

    /**
     * @var ?string Provider employee id of this document.
     *
     * @Groups({"read", "write"})
     * @Assert\Length(min=36, max=36)
     * @ORM\Column(type="string", length=36)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "example"="e2984465-190a-4562-829e-a8cca81aa35d"
     *         }
     *     }
     * )
     */
    private ?string $providerEmployeeId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getBase64(): string
    {
        return $this->base64;
    }

    public function setBase64(string $base64): self
    {
        $this->base64 = $base64;

        return $this;
    }

    public function getStudentId(): ?string
    {
        return $this->studentId;
    }

    public function setStudentId(?string $studentId): self
    {
        $this->studentId = $studentId;

        return $this;
    }

    public function getProviderEmployeeId(): ?string
    {
        return $this->providerEmployeeId;
    }

    public function setProviderEmployeeId(?string $providerEmployeeId): self
    {
        $this->providerEmployeeId = $providerEmployeeId;

        return $this;
    }
}
