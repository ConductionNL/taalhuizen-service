<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use App\Resolver\DocumentMutationResolver;
use App\Resolver\DocumentQueryCollectionResolver;
use App\Resolver\DocumentQueryItemResolver;
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
     * @ORM\Column(type="uuid", unique=true, nullable=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private UuidInterface $id;

//   name of the document, was called in the graphql-schema 'filename', changed to 'name' related to schema.org
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

//   base64 of the document, was called in the graphql-schema 'base64data', changed to 'base64' related to schema.org
    /**
     * @ORM\Column(type="text")
     */
    private $base64;

//  @todo look at how we want to handle the ids. Top 2 are linked together and the bottom 2
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $studentId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $studentDocumentId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederEmployeeId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederEmployeeDocumentId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
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

    public function getBase64(): ?string
    {
        return $this->base64;
    }

    public function setBase64(string $base64): self
    {
        $this->base64 = $base64;

        return $this;
    }

    public function getAanbiederEmployeeId(): ?string
    {
        return $this->aanbiederEmployeeId;
    }

    public function setAanbiederEmployeeId(?string $aanbiederEmployeeId): self
    {
        $this->aanbiederEmployeeId = $aanbiederEmployeeId;

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
