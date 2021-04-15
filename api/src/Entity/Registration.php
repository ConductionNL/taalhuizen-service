<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\RegistrationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=RegisterStudentRepository::class)
 */
class Registration
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $taalhuisId;

    /**
     * @ORM\ManyToOne(targetEntity=RegisterStudent::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $student;

    /**
     * @ORM\ManyToOne(targetEntity=RegisterStudentRegistrar::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $registrar;

    /**
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $memo;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaalhuisId(): ?string
    {
        return $this->taalhuisId;
    }

    public function setTaalhuisId(?string $taalhuisId): self
    {
        $this->taalhuisId = $taalhuisId;

        return $this;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): self
    {
        $this->memo = $memo;

        return $this;
    }

    public function getStudent(): ?RegisterStudent
    {
        return $this->student;
    }

    public function setStudent(?RegisterStudent $student): self
    {
        $this->student = $student;

        return $this;
    }

    public function getRegistrar(): ?RegisterStudentRegistrar
    {
        return $this->registrar;
    }

    public function setRegistrar(?RegisterStudentRegistrar $registrar): self
    {
        $this->registrar = $registrar;

        return $this;
    }
}
