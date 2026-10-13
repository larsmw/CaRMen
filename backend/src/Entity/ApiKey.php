<?php

namespace App\Entity;

use App\Repository\ApiKeyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A key for an integration (website form, another system) to call the API without a user
 * login. The key is "crm_<prefix>_<secret>"; only its SHA-256 hash is stored, so the full
 * key is shown once at creation and can't be recovered. The prefix identifies the key
 * (lookup, and recognisable in the UI) without being secret.
 */
#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
class ApiKey
{
    public const SCOPE_LEADS_CREATE = 'leads:create';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 16, unique: true)]
    private string $prefix;

    #[ORM\Column(length: 64)]
    private string $keyHash;

    /** @var string[] */
    #[ORM\Column(type: 'json')]
    private array $scopes;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $createdBy;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    /** @param string[] $scopes */
    private function __construct(string $name, string $prefix, string $keyHash, array $scopes, ?User $createdBy)
    {
        $this->id        = Uuid::v7();
        $this->name      = $name;
        $this->prefix    = $prefix;
        $this->keyHash   = $keyHash;
        $this->scopes    = $scopes;
        $this->createdBy = $createdBy;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * @param string[] $scopes
     * @return array{0: self, 1: string} the key entity and the plain key (show it once)
     */
    public static function generate(string $name, array $scopes, ?User $createdBy): array
    {
        $prefix = bin2hex(random_bytes(4));
        $plain  = 'crm_' . $prefix . '_' . rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');

        return [new self($name, $prefix, self::hash($plain), $scopes, $createdBy), $plain];
    }

    public static function hash(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    /** The prefix part of a presented key, or null if it isn't shaped like one of ours. */
    public static function prefixOf(string $plainKey): ?string
    {
        return preg_match('/^crm_([0-9a-f]{8})_[A-Za-z0-9_-]{32}$/', $plainKey, $m) ? $m[1] : null;
    }

    public function matches(string $plainKey): bool
    {
        return hash_equals($this->keyHash, self::hash($plainKey));
    }

    public function isActive(): bool { return $this->revokedAt === null; }
    public function hasScope(string $scope): bool { return \in_array($scope, $this->scopes, true); }

    public function revoke(): void { $this->revokedAt ??= new \DateTimeImmutable(); }
    public function markUsed(): void { $this->lastUsedAt = new \DateTimeImmutable(); }

    public function getId(): Uuid { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getPrefix(): string { return $this->prefix; }
    /** @return string[] */
    public function getScopes(): array { return $this->scopes; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastUsedAt(): ?\DateTimeImmutable { return $this->lastUsedAt; }
    public function getRevokedAt(): ?\DateTimeImmutable { return $this->revokedAt; }
}
