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
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use App\Repository\DealRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DealRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Assert\Callback([Deal::class, 'validateAccountOrContact'])]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_SALES') or is_granted('DEAL_CREATE')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_SALES') or is_granted('DEAL_EDIT')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('DEAL_DELETE')"),
    ],
    normalizationContext: ['groups' => ['deal:read']],
    denormalizationContext: ['groups' => ['deal:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'stage' => 'exact',
    'account.name' => 'partial',
])]
#[ApiFilter(RangeFilter::class, properties: ['value', 'closeDate'])]
class Deal
{
    // Pipeline stages
    const STAGE_PROSPECTING   = 'prospecting';
    const STAGE_QUALIFICATION = 'qualification';
    const STAGE_PROPOSAL      = 'proposal';
    const STAGE_NEGOTIATION   = 'negotiation';
    const STAGE_CLOSED_WON    = 'closed_won';
    const STAGE_CLOSED_LOST   = 'closed_lost';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['deal:read'])]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['deal:read', 'deal:write'])]
    private string $title;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'deals')]
    #[Groups(['deal:read', 'deal:write'])]
    private ?Account $account = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[Groups(['deal:read', 'deal:write'])]
    private ?Contact $primaryContact = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['deal:read', 'deal:write'])]
    private ?User $owner = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Assert\PositiveOrZero]
    #[Groups(['deal:read', 'deal:write'])]
    private string $value = '0.00';

    #[ORM\Column(length: 3, options: ['default' => 'USD'])]
    #[Groups(['deal:read', 'deal:write'])]
    private string $currency = 'USD';

    #[ORM\Column(length: 50)]
    #[Groups(['deal:read', 'deal:write'])]
    private string $stage = self::STAGE_PROSPECTING;

    #[ORM\Column(type: 'smallint', options: ['default' => 50])]
    #[Assert\Range(min: 0, max: 100)]
    #[Groups(['deal:read', 'deal:write'])]
    private int $probability = 50;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['deal:read', 'deal:write'])]
    private ?\DateTimeInterface $closeDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['deal:read', 'deal:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['deal:read', 'deal:write'])]
    private ?string $lostReason = null;

    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'deal')]
    private Collection $activities;

    #[ORM\Column]
    #[Groups(['deal:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['deal:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->activities = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): Uuid { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getAccount(): ?Account { return $this->account; }
    public function setAccount(?Account $account): static { $this->account = $account; return $this; }

    public function getPrimaryContact(): ?Contact { return $this->primaryContact; }
    public function setPrimaryContact(?Contact $primaryContact): static { $this->primaryContact = $primaryContact; return $this; }

    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $owner): static { $this->owner = $owner; return $this; }

    public function getValue(): string { return $this->value; }
    public function setValue(string $value): static { $this->value = $value; return $this; }

    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): static { $this->currency = $currency; return $this; }

    public function getStage(): string { return $this->stage; }
    public function setStage(string $stage): static { $this->stage = $stage; return $this; }

    public function getProbability(): int { return $this->probability; }
    public function setProbability(int $probability): static { $this->probability = $probability; return $this; }

    public function getCloseDate(): ?\DateTimeInterface { return $this->closeDate; }
    public function setCloseDate(?\DateTimeInterface $closeDate): static { $this->closeDate = $closeDate; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getLostReason(): ?string { return $this->lostReason; }
    public function setLostReason(?string $lostReason): static { $this->lostReason = $lostReason; return $this; }

    public function getActivities(): Collection { return $this->activities; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public static function validateAccountOrContact(self $deal, \Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ($deal->getAccount() === null && $deal->getPrimaryContact() === null) {
            $context->buildViolation('A deal must have either an account (B2B) or a contact (B2C).')
                ->atPath('account')
                ->addViolation();
        }
    }
}
