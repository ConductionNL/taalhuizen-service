<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use App\Resolver\ParticipationMutationResolver;
use App\Resolver\ParticipationQueryCollectionResolver;
use App\Resolver\ParticipationQueryItemResolver;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * All properties that the DTO entity Participation holds.
 *
 * DTO Participation exists of properties based on the following jira epics: https://lifely.atlassian.net/browse/BISC-63 and https://lifely.atlassian.net/browse/BISC-113.
 * And mainly the following issue: https://lifely.atlassian.net/browse/BISC-91
 * The learningNeedOutCome input fields are a recurring thing throughout multiple DTO entities, that is why the LearningNeedOutCome Entity was created and used here instead of matching the exact properties in the graphql schema.
 * Notable is that a few properties are renamed here, compared to the graphql schema, this was mostly done for consistency and cleaner names.
 * Translations from Dutch to English, but also shortening names by removing words from the names that had no added value to describe the property itself and that were just added before the name of each property like: 'details'.
 * The other notable change here, is that there are no groupId or providerEmployeeId properties present in this Entity (for connecting a group or mentor to this Participation). This is because custom endpoint can be used for this purpose.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     itemOperations={
 *          "get",
 *          "put",
 *          "delete"
 *     },
 *     collectionOperations={
 *          "get",
 *          "post",
 *          "add_or_remove_mentor"={
 *              "method"="POST",
 *              "path"="/participations/{uuid}/mentor/{mentorId}",
 *              "swagger_context" = {
 *                  "summary"="Add a mentor to this Participation, or remove the one connected to it.",
 *                  "description"="Add a mentor to this Participation, or remove the one connected to it."
 *              }
 *          },
 *          "update_mentor"={
 *              "method"="POST",
 *              "path"="/participations/{uuid}/mentor",
 *              "swagger_context" = {
 *                  "summary"="Update the Participation presence properties for the mentor connected to this participation.",
 *                  "description"="Update the Participation presence properties for the mentor connected to this participation."
 *              }
 *          },
 *          "add_or_remove_group"={
 *              "method"="POST",
 *              "path"="/participations/{uuid}/group/{groupId}",
 *              "swagger_context" = {
 *                  "summary"="Add a group to this Participation, or remove the one connected to it.",
 *                  "description"="Add a group to this Participation, or remove the one connected to it."
 *              }
 *          },
 *          "update_group"={
 *              "method"="POST",
 *              "path"="/participations/{uuid}/group",
 *              "swagger_context" = {
 *                  "summary"="Update the Participation presence properties for the group connected to this participation.",
 *                  "description"="Update the Participation presence properties for the group connected to this participation."
 *              }
 *          }
 *     })
 * @ORM\Entity(repositoryClass=ParticipationRepository::class)
 */
class Participation
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
     * @var String|null A contact component provider id of this Participation. <br /> **Either ProviderName or; ProviderId & ProviderNote is required!**
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="497f6eca-6276-4993-bfeb-53cbbbba6f08"
     *         }
     *     }
     * )
     */
    private ?string $providerId;

    /**
     * @var String|null The provider name of this Participation. <br /> **Either ProviderName or; ProviderId & ProviderNote is required!**
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="Provider X"
     *         }
     *     }
     * )
     */
    private ?string $providerName;

    /**
     * @var ?string Provider note of this participation. <br /> **Either ProviderName or; ProviderId & ProviderNote is required!**
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
     *             "example"="Explanation of Provider X"
     *         }
     *     }
     * )
     */
    private ?string $providerNote;

    /**
     * @var ?string Offer name of this participation
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
     *             "example"="Offer X"
     *         }
     *     }
     * )
     */
    private ?string $offerName;

    /**
     * @var ?string Offer course of this participation.
     *
     * @Assert\Choice({"LANGUAGE", "MATH", "DIGITAL", "OTHER"})
     *
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"LANGUAGE", "MATH", "DIGITAL", "OTHER"},
     *             "example"="LANGUAGE"
     *         }
     *     }
     * )
     */
    private ?string $offerCourse;

    /**
     * @var ?LearningNeedOutCome The learning need out come of this participation.
     *
     * @Groups({"read","write"})
     * @ORM\OneToOne(targetEntity=LearningNeedOutCome::class, cascade={"persist", "remove"})
     * @ApiSubresource()
     * @ORM\JoinColumn(nullable=true)
     * @MaxDepth(1)
     */
    private ?LearningNeedOutCome $learningNeedOutCome;

    /**
     * @var bool|null The isFormal boolean of this LearningNeedOutcome.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="bool",
     *             "example"="false"
     *         }
     *     }
     * )
     */
    private ?bool $isFormal;

    /**
     * @var String|null The group formation of this LearningNeedOutcome.
     *
     * @Groups({"read","write"})
     * @Assert\Choice({"INDIVIDUALLY", "IN_A_GROUP"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"INDIVIDUALLY", "IN_A_GROUP"},
     *             "example"="INDIVIDUALLY"
     *         }
     *     }
     * )
     */
    private ?string $groupFormation;

    /**
     * @var float|null The total class hours of this LearningNeedOutcome.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="float", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="float",
     *             "example"="30"
     *         }
     *     }
     * )
     */
    private ?float $totalClassHours;

    /**
     * @var bool|null The certificate will be awarded boolean of this LearningNeedOutcome.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="bool",
     *             "example"="true"
     *         }
     *     }
     * )
     */
    private ?bool $certificateWillBeAwarded;

    /**
     * @var DateTimeInterface|null The start date of this participation.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="DateTime",
     *             "example"="11-04-2021"
     *         }
     *     }
     * )
     */
    private ?DateTimeInterface $startDate;

    /**
     * @var DateTimeInterface|null The end date of this participation.
     *
     * @Groups({"read","write"})
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="DateTime",
     *             "example"="11-11-2021"
     *         }
     *     }
     * )
     */
    private ?DateTimeInterface $endDate;

    /**
     * @var ?string Details engagements of this participation
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
     *             "example"="Engagements details"
     *         }
     *     }
     * )
     */
    private ?string $engagements;

    /**
     * @var string The id of the LearningNeed connected to this Participation.
     *
     * @Assert\NotNull
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=255)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "example"="497f6eca-6276-4993-bfeb-53cbbbba6f08"
     *         }
     *     }
     * )
     */
    private string $learningNeedId;

    // Moved to custom endpoints, only used when updating a participation group or a participation mentor.
//    /**
//     * @var ?string Presence engagements of this participation.
//     *
//     * @Assert\Length(
//     *     max = 255
//     * )
//     * @Groups({"read","write"})
//     * @ORM\Column(type="string", length=255, nullable=true)
//     */
//    private ?string $presenceEngagements;
//
//    /**
//     * @var ?DateTime Presence start date of this participation.
//     *
//     * @Groups({"read","write"})
//     * @ORM\Column(type="datetime", nullable=true)
//     */
//    private ?DateTime $presenceStartDate;
//
//    /**
//     * @var ?DateTime Presence end date of this participation.
//     *
//     * @Groups({"read","write"})
//     * @ORM\Column(type="datetime", nullable=true)
//     */
//    private ?DateTime $presenceEndDate;
//
//    /**
//     * @var ?string Currently following course professionalism of this Employee.
//     *
//     * @Assert\Choice({"MOVED", "JOB", "ILLNESS", "DEATH", "COMPLETED_SUCCESSFULLY", "FAMILY_CIRCUMSTANCES", "DOES_NOT_MEET_EXPECTATIONS", "OTHER"})
//     * @Groups({"read", "write"})
//     * @ORM\Column(type="string", length=255, nullable=true)
//     * @ApiProperty(
//     *     attributes={
//     *         "openapi_context"={
//     *             "type"="string",
//     *             "enum"={"MOVED", "JOB", "ILLNESS", "DEATH", "COMPLETED_SUCCESSFULLY", "FAMILY_CIRCUMSTANCES", "DOES_NOT_MEET_EXPECTATIONS", "OTHER"},
//     *             "example"="MOVED"
//     *         }
//     *     }
//     * )
//     */
//    private ?string $presenceEndParticipationReason;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

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

    public function getLearningNeedOutCome(): LearningNeedOutCome
    {
        return $this->learningNeedOutCome;
    }

    public function setLearningNeedOutCome(?LearningNeedOutCome $learningNeedOutCome): self
    {
        $this->learningNeedOutCome = $learningNeedOutCome;

        return $this;
    }

    public function getIsFormal(): ?bool
    {
        return $this->isFormal;
    }

    public function setIsFormal(?bool $isFormal): self
    {
        $this->isFormal = $isFormal;

        return $this;
    }

    public function getGroupFormation(): ?string
    {
        return $this->groupFormation;
    }

    public function setGroupFormation(?string $groupFormation): self
    {
        $this->groupFormation = $groupFormation;

        return $this;
    }

    public function getTotalClassHours(): ?float
    {
        return $this->totalClassHours;
    }

    public function setTotalClassHours(?float $totalClassHours): self
    {
        $this->totalClassHours = $totalClassHours;

        return $this;
    }

    public function getCertificateWillBeAwarded(): ?bool
    {
        return $this->certificateWillBeAwarded;
    }

    public function setCertificateWillBeAwarded(?bool $certificateWillBeAwarded): self
    {
        $this->certificateWillBeAwarded = $certificateWillBeAwarded;

        return $this;
    }

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getEngagements(): ?string
    {
        return $this->engagements;
    }

    public function setEngagements(?string $engagements): self
    {
        $this->engagements = $engagements;

        return $this;
    }

    public function getLearningNeedId(): string
    {
        return $this->learningNeedId;
    }

    public function setLearningNeedId(string $learningNeedId): self
    {
        $this->learningNeedId = $learningNeedId;

        return $this;
    }

    // Moved to custom endpoints, only used when updating a participation group or a participation mentor.
//    public function getPresenceEngagements(): ?string
//    {
//        return $this->presenceEngagements;
//    }
//
//    public function setPresenceEngagements(?string $presenceEngagements): self
//    {
//        $this->presenceEngagements = $presenceEngagements;
//
//        return $this;
//    }
//
//    public function getPresenceStartDate(): ?\DateTimeInterface
//    {
//        return $this->presenceStartDate;
//    }
//
//    public function setPresenceStartDate(?\DateTimeInterface $presenceStartDate): self
//    {
//        $this->presenceStartDate = $presenceStartDate;
//
//        return $this;
//    }
//
//    public function getPresenceEndDate(): ?\DateTimeInterface
//    {
//        return $this->presenceEndDate;
//    }
//
//    public function setPresenceEndDate(?\DateTimeInterface $presenceEndDate): self
//    {
//        $this->presenceEndDate = $presenceEndDate;
//
//        return $this;
//    }
//
//    public function getPresenceEndParticipationReason(): ?string
//    {
//        return $this->presenceEndParticipationReason;
//    }
//
//    public function setPresenceEndParticipationReason(?string $presenceEndParticipationReason): self
//    {
//        $this->presenceEndParticipationReason = $presenceEndParticipationReason;
//
//        return $this;
//    }
}
