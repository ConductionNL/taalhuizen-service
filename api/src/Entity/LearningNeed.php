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

/**
 * @ApiResource(
 *     graphql={
 *          "item_query" = {
 *              "item_query" = LearningNeedQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = LearningNeedQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = LearningNeedMutationResolver::class,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = LearningNeedMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = LearningNeedMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     },
 *  collectionOperations={
 *          "get",
 *          "get_learning_need"={
 *              "method"="GET",
 *              "path"="/learning_needs/{id}",
 *              "swagger_context" = {
 *                  "summary"="Gets a specific learningNeed",
 *                  "description"="Returns a learningNeed"
 *              }
 *          },
 *          "delete_learning_need"={
 *              "method"="GET",
 *              "path"="/learning_needs/{id}/delete",
 *              "swagger_context" = {
 *                  "summary"="Deletes a specific learningNeed",
 *                  "description"="Returns true if this learningNeed was deleted"
 *              }
 *          },
 *          "post"
 *     },
 * )
 * @ApiFilter(SearchFilter::class, properties={"studentId": "exact"})
 * @ORM\Entity(repositoryClass=LearningNeedRepository::class)
 */
class LearningNeed
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
    private UuidInterface $id;

    /**
     * @var ?string Description of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $description;

    /**
     * @var ?string Motivation of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $motivation;

    /**
     * @var ?string Desired out comes goal of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredOutComesGoal;

    /**
     * @var ?string Desired out comes topic of this learning need.
     *
     * @Assert\Choice({"DUTCH_READING", "DUTCH_WRITING", "MATH_NUMBERS", "MATH_PROPORTION", "MATH_GEOMETRY", "MATH_LINKS", "DIGITAL_USING_ICT_SYSTEMS", "DIGITAL_SEARCHING_INFORMATION", "DIGITAL_PROCESSING_INFORMATION", "DIGITAL_COMMUNICATION", "KNOWLEDGE", "SKILLS", "ATTITUDE", "BEHAVIOUR", "OTHER"})
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredOutComesTopic;

    /**
     * @var ?string Desired outcomes topic other of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredOutComesTopicOther;

    /**
     * @var ?string Desired out comes topic of this learning need.
     *
     * @Assert\Choice({"FAMILY_AND_PARENTING", "LABOR_MARKET_AND_WORK", "HEALTH_AND_WELLBEING", "ADMINISTRATION_AND_FINANCE", "HOUSING_AND_NEIGHBORHOOD", "SELFRELIANCE", "OTHER"})
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredOutComesApplication;

    /**
     * @var ?string Desired out comes application other of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredOutComesApplicationOther;

    /**
     * @var ?string Desired out comes level of this learning need.
     *
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredOutComesLevel;

    /**
     * @var ?string Desired out comes level other of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $desiredOutComesLevelOther;

    /**
     * @var ?string Offer desired offer of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $offerDesiredOffer;

    /**
     * @var ?string Offer advised offer of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $offerAdvisedOffer;

    /**
     * @var ?string Offer difference of this learning need.
     *
     * @Assert\Choice({"INFLOW", "NLQF1", "NLQF2", "NLQF3", "NLQF4", "OTHER"})
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $offerDifference;

    /**
     * @var ?string Offer difference other of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $offerDifferenceOther;

    /**
     * @var ?string Offer engagements of this learning need.
     *
     *  @Assert\Length(
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
     */
    private ?Participation $participations;

    /**
     * @var ?string Student id of this learning need.
     *
     *  @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $studentId;

    /**
     * @var ?string The id of the objectEntity of an eav/learning_need.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $learningNeedId;

    /**
     * @var ?string The url of the objectEntity of an eav/learning_need '@eav'.
     *
     * @Groups({"write"})
     * @Assert\Url
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $learningNeedUrl;

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

    public function getDesiredOutComesGoal(): ?string
    {
        return $this->desiredOutComesGoal;
    }

    public function setDesiredOutComesGoal(?string $desiredOutComesGoal): self
    {
        $this->desiredOutComesGoal = $desiredOutComesGoal;

        return $this;
    }

    public function getDesiredOutComesTopic(): ?string
    {
        return $this->desiredOutComesTopic;
    }

    public function setDesiredOutComesTopic(?string $desiredOutComesTopic): self
    {
        $this->desiredOutComesTopic = $desiredOutComesTopic;

        return $this;
    }

    public function getDesiredOutComesTopicOther(): ?string
    {
        return $this->desiredOutComesTopicOther;
    }

    public function setDesiredOutComesTopicOther(?string $desiredOutComesTopicOther): self
    {
        $this->desiredOutComesTopicOther = $desiredOutComesTopicOther;

        return $this;
    }

    public function getDesiredOutComesApplication(): ?string
    {
        return $this->desiredOutComesApplication;
    }

    public function setDesiredOutComesApplication(?string $desiredOutComesApplication): self
    {
        $this->desiredOutComesApplication = $desiredOutComesApplication;

        return $this;
    }

    public function getDesiredOutComesApplicationOther(): ?string
    {
        return $this->desiredOutComesApplicationOther;
    }

    public function setDesiredOutComesApplicationOther(?string $desiredOutComesApplicationOther): self
    {
        $this->desiredOutComesApplicationOther = $desiredOutComesApplicationOther;

        return $this;
    }

    public function getDesiredOutComesLevel(): ?string
    {
        return $this->desiredOutComesLevel;
    }

    public function setDesiredOutComesLevel(?string $desiredOutComesLevel): self
    {
        $this->desiredOutComesLevel = $desiredOutComesLevel;

        return $this;
    }

    public function getDesiredOutComesLevelOther(): ?string
    {
        return $this->desiredOutComesLevelOther;
    }

    public function setDesiredOutComesLevelOther(?string $desiredOutComesLevelOther): self
    {
        $this->desiredOutComesLevelOther = $desiredOutComesLevelOther;

        return $this;
    }

    public function getOfferDesiredOffer(): ?string
    {
        return $this->offerDesiredOffer;
    }

    public function setOfferDesiredOffer(?string $offerDesiredOffer): self
    {
        $this->offerDesiredOffer = $offerDesiredOffer;

        return $this;
    }

    public function getOfferAdvisedOffer(): ?string
    {
        return $this->offerAdvisedOffer;
    }

    public function setOfferAdvisedOffer(?string $offerAdvisedOffer): self
    {
        $this->offerAdvisedOffer = $offerAdvisedOffer;

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

    public function getParticipations(): ?Participation
    {
        return $this->participations;
    }

    public function setParticipations(?Participation $participations): self
    {
        $this->participations = $participations;

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

    public function getLearningNeedId(): ?string
    {
        return $this->learningNeedId;
    }

    public function setLearningNeedId(?string $learningNeedId): self
    {
        $this->learningNeedId = $learningNeedId;

        return $this;
    }

    public function getLearningNeedUrl(): ?string
    {
        return $this->learningNeedUrl;
    }

    public function setLearningNeedUrl(?string $learningNeedUrl): self
    {
        $this->learningNeedUrl = $learningNeedUrl;

        return $this;
    }

}
