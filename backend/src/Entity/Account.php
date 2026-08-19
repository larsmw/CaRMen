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
use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_SALES') or is_granted('ACCOUNT_CREATE')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_SALES') or is_granted('ACCOUNT_EDIT')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('ACCOUNT_DELETE')"),
    ],
    normalizationContext: ['groups' => ['account:read']],
    denormalizationContext: ['groups' => ['account:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'industry' => 'exact',
    'country' => 'exact',
])]
class Account
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['account:read', 'contact:read', 'deal:read'])]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['account:read', 'account:write', 'contact:read', 'deal:read'])]
    private string $name;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $industry = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $website = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $addressLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $country = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?int $employeeCount = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $annualRevenue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['account:read', 'account:write'])]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'account')]
    #[Groups(['account:read'])]
    private Collection $contacts;

    #[ORM\OneToMany(targetEntity: Deal::class, mappedBy: 'account')]
    private Collection $deals;

    #[ORM\Column]
    #[Groups(['account:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['account:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->contacts = new ArrayCollection();
        $this->deals = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTimeImmutable(); }

    public function getId(): Uuid { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getIndustry(): ?string { return $this->industry; }
    public function setIndustry(?string $industry): static { $this->industry = $industry; return $this; }

    public function getWebsite(): ?string { return $this->website; }
    public function setWebsite(?string $website): static { $this->website = $website; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getAddressLine1(): ?string { return $this->addressLine1; }
    public function setAddressLine1(?string $addressLine1): static { $this->addressLine1 = $addressLine1; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): static { $this->country = $country; return $this; }

    public function getEmployeeCount(): ?int { return $this->employeeCount; }
    public function setEmployeeCount(?int $employeeCount): static { $this->employeeCount = $employeeCount; return $this; }

    public function getAnnualRevenue(): ?string { return $this->annualRevenue; }
    public function setAnnualRevenue(?string $annualRevenue): static { $this->annualRevenue = $annualRevenue; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getContacts(): Collection { return $this->contacts; }
    public function getDeals(): Collection { return $this->deals; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
