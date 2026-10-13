<?php

namespace App\Security;

use App\Entity\ApiKey;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Who is calling when a request is authenticated with an API key: the integration, not a
 * person. Its roles come from the key's scopes, e.g. leads:create -> ROLE_API_LEADS_CREATE,
 * so a key can never reach anything that requires a user role.
 */
final class ApiKeyUser implements UserInterface
{
    public function __construct(private ApiKey $apiKey) {}

    public function getApiKey(): ApiKey { return $this->apiKey; }

    public function getUserIdentifier(): string
    {
        return 'api-key:' . $this->apiKey->getPrefix();
    }

    public function getRoles(): array
    {
        return array_map(
            fn (string $scope) => 'ROLE_API_' . strtoupper(str_replace(':', '_', $scope)),
            $this->apiKey->getScopes(),
        );
    }

    public function eraseCredentials(): void {}
}
