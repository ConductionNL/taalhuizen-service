<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\GroupRepository;
use App\Resolver\GroupMutationResolver;
use App\Resolver\GroupQueryCollectionResolver;
use App\Resolver\GroupQueryItemResolver;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     },
 *     graphql={
 *          "item_query" = {
 *              "item_query" = GroupQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = GroupQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = GroupMutationResolver::class,
 *              "write" = false,
 *
 *          },
 *          "update" = {
 *              "mutation" = GroupMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = GroupMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "active" = {
 *              "collection_query" = GroupQueryCollectionResolver::class
 *          },
 *          "future" = {
 *              "collection_query" = GroupQueryCollectionResolver::class
 *          },
 *          "completed" = {
 *              "collection_query" = GroupQueryCollectionResolver::class
 *          },
 *          "participantsOfThe" = {
 *              "collection_query" = GroupQueryCollectionResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}}
 *          },
 *          "changeTeachersOfThe" = {
 *              "mutation" = GroupMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     }
 * )
 * @ORM\Entity(repositoryClass=GroupRepository::class)
 * @ApiFilter(SearchFilter::class, properties={"aanbiederId" = "exact"})
 * @ORM\Table(name="`group`")
 */
class Group
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aanbiederId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $typeCourse;

    /**
     * @Groups({"write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?string $learningNeedOutCome;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $availability = [];

    /**
     * @ORM\Column(type="string", length=2550, nullable=true)
     */
    private $availabilityNotes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $generalLocation;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $generalParticipantsMin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $generalParticipantsMax;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $generalEvaluation;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $aanbiederEmployeeIds = [];

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

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTypeCourse(): ?string
    {
        return $this->typeCourse;
    }

    public function setTypeCourse(?string $typeCourse): self
    {
        $this->typeCourse = $typeCourse;

        return $this;
    }

    public function getLearningNeedOutCome(): ?string
    {
        return $this->learningNeedOutCome;
    }

    public function setLearningNeedOutCome(string $learningNeedOutCome): self
    {
        $this->learningNeedOutCome = $learningNeedOutCome;

        return $this;
    }

    public function getAvailabilityNotes(): ?string
    {
        return $this->availabilityNotes;
    }

    public function setAvailabilityNotes(?string $availabilityNotes): self
    {
        $this->availabilityNotes = $availabilityNotes;

        return $this;
    }

    public function getGeneralLocation(): ?string
    {
        return $this->generalLocation;
    }

    public function setGeneralLocation(?string $generalLocation): self
    {
        $this->generalLocation = $generalLocation;

        return $this;
    }

    public function getGeneralParticipantsMin(): ?int
    {
        return $this->generalParticipantsMin;
    }

    public function setGeneralParticipantsMin(?int $generalParticipantsMin): self
    {
        $this->generalParticipantsMin = $generalParticipantsMin;

        return $this;
    }

    public function getGeneralParticipantsMax(): ?int
    {
        return $this->generalParticipantsMax;
    }

    public function setGeneralParticipantsMax(?int $generalParticipantsMax): self
    {
        $this->generalParticipantsMax = $generalParticipantsMax;

        return $this;
    }

    public function getGeneralEvaluation(): ?string
    {
        return $this->generalEvaluation;
    }

    public function setGeneralEvaluation(?string $generalEvaluation): self
    {
        $this->generalEvaluation = $generalEvaluation;

        return $this;
    }

    public function getAanbiederEmployeeIds(): ?array
    {
        return $this->aanbiederEmployeeIds;
    }

    public function setAanbiederEmployeeIds(?array $aanbiederEmployeeIds): self
    {
        $this->aanbiederEmployeeIds = $aanbiederEmployeeIds;

        return $this;
    }

    public function getAvailability(): ?array
    {
        return $this->availability;
    }

    public function setAvailability(?array $availability): self
    {
        $this->availability = $availability;

        return $this;
    }

    public function getAanbiederId(): ?string
    {
        return $this->aanbiederId;
    }

    public function setAanbiederId(?string $aanbiederId): self
    {
        $this->aanbiederId = $aanbiederId;

        return $this;
    }
}
