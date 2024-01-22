<?php

namespace Fakturoid\Tests\Oauth2;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Fakturoid\Oauth2\Provider\FakturoidProvider;

class FakturoidTest extends TestCase
{
    protected FakturoidProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new FakturoidProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'mock_redirect_uri',
            'userAgent' => 'mock_user_agent (email@me.com)'
        ]);
    }

    public function testUserAgent(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('userAgent must be in format "Name (email)"');
        new FakturoidProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'mock_redirect_uri',
            'userAgent' => 'not valid user agent'
        ]);
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        $this->assertIsArray($uri);
        $this->assertArrayHasKey('query', $uri);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
    }

    public function testGetBaseAccessTokenUrl(): void
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/api/v3/oauth/token', $uri['path']);
    }

    public function testGetAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/api/v3/oauth', $uri['path']);
    }

    public function testAuthorizationCode(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getHeader')
            ->willReturn(['application/json']);

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->getAnonymousStreamInterface());

        $client = $this->createMock(ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertNull(
            $token->getResourceOwnerId(),
            'Fakturoid does not return user ID with access token. Expected null.'
        );
    }

    public function testClientCredentials(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getHeader')
            ->willReturn(['application/json']);

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($this->getAnonymousStreamInterface());

        $client = $this->createMock(ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('client_credentials');

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertNull(
            $token->getResourceOwnerId(),
            'Fakturoid does not return user ID with access token. Expected null.'
        );
    }

    private function getAnonymousStreamInterface(): StreamInterface
    {
        return new class implements StreamInterface
        {
            public function __toString(): string
            {
                return $this->getContents();
            }

            public function close(): void
            {
            }

            /**
             * @return resource|null
             */
            public function detach()
            {
                return null;
            }

            public function getSize(): ?int
            {
                return 0;
            }

            public function tell(): int
            {
                return 0;
            }

            public function eof(): bool
            {
                return false;
            }

            public function isSeekable(): bool
            {
                return false;
            }

            public function seek(int $offset, int $whence = SEEK_SET): void
            {
            }

            public function rewind(): void
            {
            }

            public function isWritable(): bool
            {
                return false;
            }

            public function write(string $string): int
            {
                return 0;
            }

            public function isReadable(): bool
            {
                return true;
            }

            public function read(int $length): string
            {
                return '';
            }

            public function getContents(): string
            {
                return '{"access_token":"mock_access_token","refresh_token":"mock_refresh_token","token_type":"bearer","expires_in":3600,"host":"mock_host"}';
            }

            public function getMetadata(?string $key = null)
            {
            }
        };
    }
}
