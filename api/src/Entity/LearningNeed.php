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
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ApiFilter(SearchFilter::class, properties={"studentId": "exact"})
 * @ORM\Entity(repositoryClass=LearningNeedRepository::class)
 */
class LearningNeed
{
//   Id of the learning need, was called in the graphql-schema 'learningNeedId'
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
     * @var ?LearningNeedOutCome The learning need out come of this learning need.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?LearningNeedOutCome $learningNeedOutCome;

    /**
     * @var string Offer desired offer of this learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $offerDesiredOffer;

    /**
     * @var string Offer advised offer of this learning need.
     *
     * @Assert\NotNull
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $offerAdvisedOffer;

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
     * @var ?Participation Participation's of this learning need.
     *
     * @Groups({"read","write"})
     * @ORM\OneToOne(targetEntity=Participation::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?Participation $participations;

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

    public function getLearningNeedOutCome(): LearningNeedOutCome
    {
        return $this->learningNeedOutCome;
    }

    public function setLearningNeedOutCome(?LearningNeedOutCome $learningNeedOutCome): self
    {
        $this->learningNeedOutCome = $learningNeedOutCome;

        return $this;
    }

    public function getOfferDesiredOffer(): string
    {
        return $this->offerDesiredOffer;
    }

    public function setOfferDesiredOffer(string $offerDesiredOffer): self
    {
        $this->offerDesiredOffer = $offerDesiredOffer;

        return $this;
    }

    public function getOfferAdvisedOffer(): string
    {
        return $this->offerAdvisedOffer;
    }

    public function setOfferAdvisedOffer(string $offerAdvisedOffer): self
    {
        $this->offerAdvisedOffer = $offerAdvisedOffer;

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

    public function getParticipations(): ?Participation
    {
        return $this->participations;
    }

    public function setParticipations(?Participation $participations): self
    {
        $this->participations = $participations;

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
