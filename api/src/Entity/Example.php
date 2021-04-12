<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

// TODO:delete this entity
/**
 * @ApiResource(
 *     collectionOperations={
 *          "get",
 *          "post",
 *      }
 * )
 */
class Example
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
     * @ORM\Column(type="json", length=255)
     */
    private ?array $data;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }
    public function getData(): ?array
    {
        return $this->data;
    }
}
