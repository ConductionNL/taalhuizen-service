<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\LearningNeedRepository;
use App\Resolver\LearningNeedMutationResolver;
use App\Resolver\LearningNeedQueryCollectionResolver;
use App\Resolver\LearningNeedQueryItemResolver;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\MaxDepth;

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
 *     collectionOperations={
 *          "get",
 *          "post",
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
     * @var string Description of this learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $description;

    /**
     * @var string Motivation of this learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $motivation;

    /**
     * @var LearningNeedOutCome The desired learning need out come of this learning need.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private LearningNeedOutCome $desiredLearningNeedOutCome;

    /**
     * @var string Desired offer of this learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $desiredOffer;

    /**
     * @var string Advised offer of this learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $advisedOffer;

    /**
     * @var string Offer difference of this learning need.
     *
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     *
     * @Assert\NotNull
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"},
     *             "example"="INFLOW"
     *         }
     *     }
     * )
     */
    private string $offerDifference;

    /**
     * @var ?string Offer difference other of this learning need.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $offerDifferenceOther;

    /**
     * @var ?string Offer engagements of this learning need.
     *
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $offerEngagements;

    /**
     * @var string Student id of this learning need.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255)
     */
    private string $studentId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getMotivation(): string
    {
        return $this->motivation;
    }

    public function setMotivation(string $motivation): self
    {
        $this->motivation = $motivation;

        return $this;
    }

    public function getDesiredLearningNeedOutCome(): LearningNeedOutCome
    {
        return $this->desiredLearningNeedOutCome;
    }

    public function setDesiredLearningNeedOutCome(LearningNeedOutCome $desiredLearningNeedOutCome): self
    {
        $this->desiredLearningNeedOutCome = $desiredLearningNeedOutCome;

        return $this;
    }

    public function getDesiredOffer(): string
    {
        return $this->desiredOffer;
    }

    public function setDesiredOffer(string $desiredOffer): self
    {
        $this->desiredOffer = $desiredOffer;

        return $this;
    }

    public function getAdvisedOffer(): string
    {
        return $this->advisedOffer;
    }

    public function setAdvisedOffer(string $advisedOffer): self
    {
        $this->advisedOffer = $advisedOffer;

        return $this;
    }

    public function getOfferDifference(): string
    {
        return $this->offerDifference;
    }

    public function setOfferDifference(string $offerDifference): self
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

    public function getStudentId(): string
    {
        return $this->studentId;
    }

    public function setStudentId(string $studentId): self
    {
        $this->studentId = $studentId;

        return $this;
    }

}
