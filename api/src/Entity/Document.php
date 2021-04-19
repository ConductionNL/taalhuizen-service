<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
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
 * @ApiResource()
 * @ORM\Entity(repositoryClass=DocumentRepository::class)
 */
class Document
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
    private $id;

    /**
     * @var string the base64 of the document
     *
     * @Assert\NotNull
     * @ORM\Column(type="text")
     */
    private $base64data;

    /**
     * @var string the name of the file
     *
     * @Assert\NotNull
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederEmployeeId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $studentId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederEmployeeDocumentId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $studentDocumentId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;
        return $this;
    }

    public function getBase64Data(): ?string
    {
        return $this->base64data;
    }

    public function setBase64Data(string $base64data): self
    {
        $this->base64data = $base64data;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getAanbiederEmployeeId(): ?string
    {
        return $this->aanbiederEmployeeId;
    }

    public function setAanbiederEmployeeId(string $aanbiederEmployeeId): self
    {
        $this->aanbiederEmployeeId = $aanbiederEmployeeId;

        return $this;
    }

    public function getStudentId(): ?string
    {
        return $this->studentId;
    }

    public function setStudentId(string $studentId): self
    {
        $this->studentId = $studentId;

        return $this;
    }

    public function getAanbiederEmployeeDocumentId(): ?string
    {
        return $this->aanbiederEmployeeDocumentId;
    }

    public function setAanbiederEmployeeDocumentId(?string $aanbiederEmployeeDocumentId): self
    {
        $this->aanbiederEmployeeDocumentId = $aanbiederEmployeeDocumentId;

        return $this;
    }

    public function getStudentDocumentId(): ?string
    {
        return $this->studentDocumentId;
    }

    public function setStudentDocumentId(?string $studentDocumentId): self
    {
        $this->studentDocumentId = $studentDocumentId;

        return $this;
    }
}
