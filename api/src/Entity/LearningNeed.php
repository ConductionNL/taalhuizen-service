<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Repository\LearningNeedRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * All properties that the DTO entity LearningNeed holds.
 *
 * DTO LearningNeed exists of properties based on the following jira epics: https://lifely.atlassian.net/browse/BISC-62 and https://lifely.atlassian.net/browse/BISC-112.
 * And mainly the following issue: https://lifely.atlassian.net/browse/BISC-86
 * The desiredLearningNeedOutCome input fields are a recurring thing throughout multiple DTO entities, that is why the LearningNeedOutCome Entity was created and used here instead of matching the exact properties in the graphql schema.
 * Notable is that a few properties are renamed here, compared to the graphql schema, this was mostly done for consistency and cleaner names.
 * Mostly shortening names by removing words from the names that had no added value to describe the property itself and that were just added before the name of each property like: 'offer'. (while the rest of the name after that also had the word offer in it)
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get"={
 *              "read"=false,
 *              "validate"=false
 *          },
 *          "put"={
 *             "read"=false,
 *          },
 *          "delete"={
 *             "read"=false,
 *             "validate"=false
 *          },
 *     },
 *     collectionOperations={
 *          "get",
 *          "post"={
 *              "read"=false,
 *              "validate"=false
 *          },
 *     })
 * @ORM\Entity(repositoryClass=LearningNeedRepository::class)
 */
class LearningNeed
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
     * @var ?string A short description of this learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="description"
     *         }
     *     }
     * )
     */
    private ?string $description = null;

    /**
     * @var ?string The motivation of a student, for this learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="I would like to get more experience on this topic"
     *         }
     *     }
     * )
     */
    private ?string $motivation = null;

    /**
     * @var ?LearningNeedOutCome The desired learning need out come of this learning need.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @Assert\Valid
     * @MaxDepth(1)
     */
    private ?LearningNeedOutCome $desiredLearningNeedOutCome = null;

    /**
     * @var ?string The desired offer for a student learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Taalhuis x in Amsterdam"
     *         }
     *     }
     * )
     */
    private ?string $desiredOffer = null;

    /**
     * @var ?string The advised offer of a student learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Taalhuis y in Amsterdam"
     *         }
     *     }
     * )
     */
    private ?string $advisedOffer = null;

    /**
     * @var ?string The difference between the desired and advised offer of this learning need.
     *
     * @Assert\Choice({"NO", "YES_DISTANCE", "YES_WAITINGLIST", "YES_OTHER"})
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"NO", "YES_DISTANCE", "YES_WAITINGLIST", "YES_OTHER"},
     *             "example"="YES_WAITINGLIST"
     *         }
     *     }
     * )
     */
    private ?string $offerDifference = null;

    /**
     * @var ?string Offer difference of this learning need, for when the OTHER option is selected.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="An other reason why there is a difference."
     *         }
     *     }
     * )
     */
    private ?string $offerDifferenceOther = null;

    /**
     * @var ?string The offer engagements for this learning need.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="An agreement"
     *         }
     *     }
     * )
     */
    private ?string $offerEngagements = null;

    /**
     * @var ?string The id of a student that this learning need is for.
     *
     * @Assert\NotNull
     * @Assert\Length(min=36, max=36)
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=36)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="e2984465-190a-4562-829e-a8cca81aa35d"
     *         }
     *     }
     * )
     */
    private ?string $studentId = null;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(?string $motivation): self
    {
        $this->motivation = $motivation;

        return $this;
    }

    public function getDesiredLearningNeedOutCome(): ?LearningNeedOutCome
    {
        return $this->desiredLearningNeedOutCome;
    }

    public function setDesiredLearningNeedOutCome(?LearningNeedOutCome $desiredLearningNeedOutCome): self
    {
        $this->desiredLearningNeedOutCome = $desiredLearningNeedOutCome;

        return $this;
    }

    public function getDesiredOffer(): ?string
    {
        return $this->desiredOffer;
    }

    public function setDesiredOffer(?string $desiredOffer): self
    {
        $this->desiredOffer = $desiredOffer;

        return $this;
    }

    public function getAdvisedOffer(): ?string
    {
        return $this->advisedOffer;
    }

    public function setAdvisedOffer(?string $advisedOffer): self
    {
        $this->advisedOffer = $advisedOffer;

        return $this;
    }

    public function getOfferDifference(): ?string
    {
        return $this->offerDifference;
    }

    public function setOfferDifference(?string $offerDifference): self
    {
        $this->offerDifference = $offerDifference;

        return $this;
    }

    public function getOfferDifferenceOther(): ?string
    {
        return $this->offerDifferenceOther;
    }

    public function setOfferDifferenceOther(?string $offerDifferenceOther): self
    {
        $this->offerDifferenceOther = $offerDifferenceOther;

        return $this;
    }

    public function getOfferEngagements(): ?string
    {
        return $this->offerEngagements;
    }

    public function setOfferEngagements(?string $offerEngagements): self
    {
        $this->offerEngagements = $offerEngagements;

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
}
