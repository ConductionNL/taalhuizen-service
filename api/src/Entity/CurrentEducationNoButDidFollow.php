<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CurrentEducationNoButDidFollowRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=CurrentEducationNoButDidFollowRepository::class)
 */
class CurrentEducationNoButDidFollow
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

    //   endDate of the education, was called in the graphql-schema 'dateUntil', changed to 'endDate' related to schema.org
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $enddate;

    //   degree ganted status of the education, was called in the graphql-schema 'gotCertificate', changed to 'degreeGrantedStatus' related to schema.org
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $degreeGrantedStatus;

    //   isced education level code of the education, was called in the graphql-schema 'level', changed to 'iscedEducationLevelCode' related to schema.org
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $iscedEducationLevelCode;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getEnddate(): ?\DateTimeInterface
    {
        return $this->enddate;
    }

    public function setEnddate(?\DateTimeInterface $enddate): self
    {
        $this->enddate = $enddate;

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

    public function getIscedEducationLevelCode(): ?string
    {
        return $this->iscedEducationLevelCode;
    }

    public function setIscedEducationLevelCode(?string $iscedEducationLevelCode): self
    {
        $this->iscedEducationLevelCode = $iscedEducationLevelCode;

        return $this;
    }
}
