<?php

namespace Fakturoid\Oauth2\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * @phpstan-type UserData array{
 *     id: int,
 *     full_name: string,
 *     email: string,
 *     avatar_url: ?string,
 *     default_account: string,
 *     accounts: AccountData[]
 * }
 * @phpstan-type AccountData array{
 *     slug: string,
 *     logo: ?string,
 *     name: string,
 *     registration_no: string,
 *     permission: string,
 *     allowed_scope: array<string>
 *}
 */
class FakturoidResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * @param UserData $response
     */
    public function __construct(
        private array $response
    ) {
    }

    public function getId(): int
    {
        return $this->response['id'];
    }

    public function getFullName(): string
    {
        return $this->response['full_name'];
    }

    public function getEmail(): string
    {
        return $this->response['email'];
    }

    public function getAvatar(): ?string
    {
        return $this->response['avatar_url'];
    }

    public function getDefaultAccount(): string
    {
        return $this->response['default_account'];
    }

    /**
     * @return AccountData[]
     */
    public function getAccounts(): array
    {
        return $this->response['accounts'] ?? [];
    }

    /**
     * @return UserData
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
