<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\StudentEducationRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get",
 *          "post",
 *     })
 * @ORM\Entity(repositoryClass=StudentEducationRepository::class)
 */
class StudentEducation
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

//   Education of the employee, was called in the graphql-schema;
// 'lastFollowedEducation', 'didGraduate', 'followingEducationRightNowYesStartDate',
// 'followingEducationRightNowYesLevel', 'followingEducationRightNowYesInstitute', 'followingEducationRightNowYesProvidesCertificate',
// 'followingEducationRightNowNoEndDate', 'followingEducationRightNowNoLevel', 'followingEducationRightNowNoGotCertificate',
// changed to 'education'(Education entity) related to schema.org
    /**
     * @var ?Education Education of this studentEducation.
     *
     * @Groups({"read", "write"})
     * @ORM\OneToOne(targetEntity=Education::class, cascade={"persist", "remove"})
     */
    private ?Education $education;

    /**
     * @var ?string Following education right now of this studentEducation.
     *
     *  @Assert\Length(
     *     max = 255
     *)
     * @Groups({"read","write"})
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(
     *     attributes={
     *         "openapi_context"={
     *             "type"="string",
     *             "enum"={"YES", "NO", "NO_BUT_DID_EARLIER"},
     *             "example"="YES"
     *         }
     *     }
     * )
     */
    private ?string $followingEducationRightNow;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $uuid): self
    {
        $this->id = $uuid;

        return $this;
    }

    public function getFollowingEducationRightNow(): ?string
    {
        return $this->followingEducationRightNow;
    }

    public function setFollowingEducationRightNow(?string $followingEducationRightNow): self
    {
        $this->followingEducationRightNow = $followingEducationRightNow;

        return $this;
    }

    public function getEducation(): ?Education
    {
        return $this->education;
    }

    public function setEducation(?Education $education): self
    {
        $this->education = $education;

        return $this;
    }
}
