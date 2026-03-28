<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\ActivityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Patch(),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ACTIVITY_DELETE')"),
    ],
    normalizationContext: ['groups' => ['activity:read']],
    denormalizationContext: ['groups' => ['activity:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'type' => 'exact',
    'status' => 'exact',
    'contact.id' => 'exact',
    'deal.id' => 'exact',
    'assignedTo.id' => 'exact',
])]
class Activity
{
    const TYPE_CALL    = 'call';
    const TYPE_EMAIL   = 'email';
    const TYPE_MEETING = 'meeting';
    const TYPE_TASK    = 'task';
    const TYPE_NOTE    = 'note';

    const STATUS_PLANNED   = 'planned';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['activity:read'])]
    private Uuid $id;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: [self::TYPE_CALL, self::TYPE_EMAIL, self::TYPE_MEETING, self::TYPE_TASK, self::TYPE_NOTE])]
    #[Groups(['activity:read', 'activity:write'])]
    private string $type;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['activity:read', 'activity:write'])]
    private string $subject;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 50, options: ['default' => 'planned'])]
    #[Groups(['activity:read', 'activity:write'])]
    private string $status = self::STATUS_PLANNED;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['activity:read', 'activity:write'])]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'activities')]
    #[Groups(['activity:read', 'activity:write'])]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: Deal::class, inversedBy: 'activities')]
    #[Groups(['activity:read', 'activity:write'])]
    private ?Deal $deal = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'activities')]
    #[Groups(['activity:read', 'activity:write'])]
    private ?User $assignedTo = null;

    #[ORM\Column]
    #[Groups(['activity:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['activity:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): Uuid { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $subject): static { $this->subject = $subject; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getScheduledAt(): ?\DateTimeInterface { return $this->scheduledAt; }
    public function setScheduledAt(?\DateTimeInterface $scheduledAt): static { $this->scheduledAt = $scheduledAt; return $this; }

    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeInterface $completedAt): static { $this->completedAt = $completedAt; return $this; }

    public function getContact(): ?Contact { return $this->contact; }
    public function setContact(?Contact $contact): static { $this->contact = $contact; return $this; }

    public function getDeal(): ?Deal { return $this->deal; }
    public function setDeal(?Deal $deal): static { $this->deal = $deal; return $this; }

    public function getAssignedTo(): ?User { return $this->assignedTo; }
    public function setAssignedTo(?User $assignedTo): static { $this->assignedTo = $assignedTo; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
