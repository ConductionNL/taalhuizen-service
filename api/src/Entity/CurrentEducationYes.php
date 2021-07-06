<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CurrentEducationYesRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=CurrentEducationYesRepository::class)
 */
class CurrentEducationYes
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

    //   startDate of the education, was called in the graphql-schema 'dateSince', changed to 'startDate' related to schema.org
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startDate;

    //   institution of the education, was called in the graphql-schema 'name', changed to 'institution' related to schema.org
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $institution;

    //   degree granted status of the education, was called in the graphql-schema 'doesProvideCertificate', changed to 'degreeGrantedStatus' related to schema.org
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $degreeGrantedStatus;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getInstitution(): ?string
    {
        return $this->institution;
    }

    public function setInstitution(?string $institution): self
    {
        $this->institution = $institution;

        return $this;
    }

    public function getDegreeGrantedStatus(): ?string
    {
        return $this->degreeGrantedStatus;
    }

    public function setDegreeGrantedStatus(?string $degreeGrantedStatus): self
    {
        $this->degreeGrantedStatus = $degreeGrantedStatus;

        return $this;
    }
}
