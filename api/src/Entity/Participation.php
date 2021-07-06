<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\ParticipationRepository;
use App\Resolver\ParticipationMutationResolver;
use App\Resolver\ParticipationQueryCollectionResolver;
use App\Resolver\ParticipationQueryItemResolver;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     graphql={
 *          "item_query" = {
 *              "item_query" = ParticipationQueryItemResolver::class,
 *              "read" = false
 *          },
 *          "collection_query" = {
 *              "collection_query" = ParticipationQueryCollectionResolver::class
 *          },
 *          "create" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "write" = false
 *          },
 *          "update" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "remove" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"id"={"type" = "ID!", "description" =  "the identifier"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "removeMentorFrom" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"participationId"={"type" = "ID!"}, "providerEmployeeId"={"type" = "ID!"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "updateMentor" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {
 *                  "participationId"={"type" = "ID!"},
 *                  "presenceEngagements"={"type" = "String"},
 *                  "presenceStartDate"={"type" = "String"},
 *                  "presenceEndDate"={"type" = "String"},
 *                  "presenceEndParticipationReason"={"type" = "String"}
 *              },
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "addGroupTo" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"participationId"={"type" = "ID!"}, "groupId"={"type" = "ID!"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "updateGroup" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {
 *                  "participationId"={"type" = "ID!"},
 *                  "presenceEngagements"={"type" = "String"},
 *                  "presenceStartDate"={"type" = "String"},
 *                  "presenceEndDate"={"type" = "String"},
 *                  "presenceEndParticipationReason"={"type" = "String"}
 *              },
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          },
 *          "removeGroupFrom" = {
 *              "mutation" = ParticipationMutationResolver::class,
 *              "args" = {"participationId"={"type" = "ID!"}, "groupId"={"type" = "ID!"}},
 *              "read" = false,
 *              "deserialize" = false,
 *              "validate" = false,
 *              "write" = false
 *          }
 *     }
 * )
 * @ApiFilter(SearchFilter::class, properties={"learningNeedId": "exact"})
 * @ORM\Entity(repositoryClass=ParticipationRepository::class)
 */
class Participation
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
     * @Groups({"write"})
     * @Assert\Choice({"ACTIVE", "COMPLETED", "REFERRED"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $providerId;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $providerName;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $providerNote;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offerName;

    /**
     * @Groups({"write"})
     * @Assert\Choice({"LANGUAGE", "MATH", "DIGITAL", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $offerCourse;

    /**
     * @Groups({"write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @MaxDepth(1)
     */
    private ?string $learningNeedOutCome;

    /**
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $detailsEngagements;

    /**
     * @var string The id of the objectEntity of an eav/learning_need.
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $learningNeedId;

    /**
     * @var string The url of the objectEntity of an eav/learning_need '@eav'.
     *
     * @Groups({"write"})
     * @Assert\Url
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $learningNeedUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $presenceEngagements;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $presenceStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $presenceEndDate;

    /**
     * @Assert\Choice({"MOVED", "JOB", "ILLNESS", "DEATH", "COMPLETED_SUCCESSFULLY", "FAMILY_CIRCUMSTANCES", "DOES_NOT_MEET_EXPECTATIONS", "OTHER"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $presenceEndParticipationReason;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $providerEmployeeId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $groupId;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(?string $providerId): self
    {
        $this->providerId = $providerId;

        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(?string $providerName): self
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getProviderNote(): ?string
    {
        return $this->providerNote;
    }

    public function setProviderNote(?string $providerNote): self
    {
        $this->providerNote = $providerNote;

        return $this;
    }

    public function getOfferName(): ?string
    {
        return $this->offerName;
    }

    public function setOfferName(?string $offerName): self
    {
        $this->offerName = $offerName;

        return $this;
    }

    public function getOfferCourse(): ?string
    {
        return $this->offerCourse;
    }

    public function setOfferCourse(?string $offerCourse): self
    {
        $this->offerCourse = $offerCourse;

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

    public function getDetailsEngagements(): ?string
    {
        return $this->detailsEngagements;
    }

    public function setDetailsEngagements(?string $detailsEngagements): self
    {
        $this->detailsEngagements = $detailsEngagements;

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

    public function getPresenceEngagements(): ?string
    {
        return $this->presenceEngagements;
    }

    public function setPresenceEngagements(?string $presenceEngagements): self
    {
        $this->presenceEngagements = $presenceEngagements;

        return $this;
    }

    public function getPresenceStartDate(): ?\DateTimeInterface
    {
        return $this->presenceStartDate;
    }

    public function setPresenceStartDate(?\DateTimeInterface $presenceStartDate): self
    {
        $this->presenceStartDate = $presenceStartDate;

        return $this;
    }

    public function getPresenceEndDate(): ?\DateTimeInterface
    {
        return $this->presenceEndDate;
    }

    public function setPresenceEndDate(?\DateTimeInterface $presenceEndDate): self
    {
        $this->presenceEndDate = $presenceEndDate;

        return $this;
    }

    public function getPresenceEndParticipationReason(): ?string
    {
        return $this->presenceEndParticipationReason;
    }

    public function setPresenceEndParticipationReason(?string $presenceEndParticipationReason): self
    {
        $this->presenceEndParticipationReason = $presenceEndParticipationReason;

        return $this;
    }

    public function getProviderEmployeeId(): ?string
    {
        return $this->providerEmployeeId;
    }

    public function setProviderEmployeeId(?string $providerEmployeeId): self
    {
        $this->providerEmployeeId = $providerEmployeeId;

        return $this;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }
}
