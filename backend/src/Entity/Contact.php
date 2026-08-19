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
use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(security: "is_granted('ROLE_SALES') or is_granted('CONTACT_CREATE')"),
        new Get(),
        new Patch(security: "is_granted('ROLE_SALES') or is_granted('CONTACT_EDIT')"),
        new Delete(security: "is_granted('ROLE_ADMIN') or is_granted('CONTACT_DELETE')"),
    ],
    normalizationContext: ['groups' => ['contact:read']],
    denormalizationContext: ['groups' => ['contact:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'firstName' => 'partial',
    'lastName' => 'partial',
    'email' => 'partial',
    'account.name' => 'partial',
])]
class Contact
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['contact:read', 'deal:read', 'activity:read'])]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['contact:read', 'contact:write', 'deal:read', 'activity:read'])]
    private string $firstName;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['contact:read', 'contact:write', 'deal:read', 'activity:read'])]
    private string $lastName;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Assert\Email]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $phone = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $mobile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $jobTitle = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $department = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $addressLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $addressLine2 = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $country = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'contacts')]
    #[Groups(['contact:read', 'contact:write'])]
    private ?Account $account = null;

    #[ORM\Column(length: 50, options: ['default' => 'lead'])]
    #[Groups(['contact:read', 'contact:write'])]
    private string $status = 'lead'; // lead, prospect, customer, inactive

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['contact:read', 'contact:write'])]
    private ?string $notes = null;

    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'contact')]
    private Collection $activities;

    #[ORM\Column]
    #[Groups(['contact:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['contact:read'])]
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

    #[Groups(['contact:read', 'deal:read', 'activity:read'])]
    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }

    public function getId(): Uuid { return $this->id; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getMobile(): ?string { return $this->mobile; }
    public function setMobile(?string $mobile): static { $this->mobile = $mobile; return $this; }

    public function getJobTitle(): ?string { return $this->jobTitle; }
    public function setJobTitle(?string $jobTitle): static { $this->jobTitle = $jobTitle; return $this; }

    public function getDepartment(): ?string { return $this->department; }
    public function setDepartment(?string $department): static { $this->department = $department; return $this; }

    public function getAccount(): ?Account { return $this->account; }
    public function setAccount(?Account $account): static { $this->account = $account; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getAddressLine1(): ?string { return $this->addressLine1; }
    public function setAddressLine1(?string $a): static { $this->addressLine1 = $a; return $this; }

    public function getAddressLine2(): ?string { return $this->addressLine2; }
    public function setAddressLine2(?string $a): static { $this->addressLine2 = $a; return $this; }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $p): static { $this->postalCode = $p; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $c): static { $this->city = $c; return $this; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $c): static { $this->country = $c; return $this; }

    public function getActivities(): Collection { return $this->activities; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
