<?php

namespace Fakturoid\Oauth2\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Client\Token\AccessToken;
use LogicException;
use Psr\Http\Message\ResponseInterface;

/**
 * @phpstan-import-type UserData from FakturoidResourceOwner
 */
class FakturoidProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        if (!isset($options['userAgent'])) {
            throw new LogicException('userAgent must be set in options');
        }
        $re = '/.{1,}\([\w+\.]+@([\w-]+\.)+[\w-]{2,4}\)/m';
        preg_match_all($re, $options['userAgent'], $matches, PREG_SET_ORDER, 0);
        if (empty($matches)) {
            throw new LogicException('userAgent must be in format "Name (email)"');
        }
        $collaborators['optionProvider'] = new AuthOptionProvider($options['userAgent']);

        parent::__construct($options, $collaborators);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://app.fakturoid.cz/api/v3/oauth';
    }

    /**
     * @param array<string, mixed> $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://app.fakturoid.cz/api/v3/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://app.fakturoid.cz/api/v3/user.json';
    }

    /**
     * @return string[]
     */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * @param mixed $data
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (!is_array($data)) {
            return;
        }
        if (!isset($data['error'])) {
            return;
        }
        $statusCode = $response->getStatusCode();
        $error = $data['error'];
        $errorDescription = $data['error_description'];
        $errorLink = ($data['error_uri'] ?? false);
        throw new IdentityProviderException(
            $statusCode . ' - ' . $errorDescription . ': ' . $error . ($errorLink ? ' (see: ' . $errorLink . ')' : ''),
            $response->getStatusCode(),
            $response
        );
    }

    /**
     * @param UserData $response
     * @return FakturoidResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new FakturoidResourceOwner($response);
    }
}
