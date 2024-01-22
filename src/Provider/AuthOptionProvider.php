<?php

namespace Fakturoid\Oauth2\Provider;

use InvalidArgumentException;
use League\OAuth2\Client\OptionProvider\OptionProviderInterface;
use League\OAuth2\Client\Provider\AbstractProvider;

class AuthOptionProvider implements OptionProviderInterface
{
    public function __construct(
        private string $userAgent
    ) {
    }

    /**
     * @param string $method
     * @param array<string, mixed> $params
     * @return array{body?:string, headers: array{'Accept': string, 'Content-Type':string, 'Authorization': string, 'User-Agent': string}}
     */
    public function getAccessTokenOptions($method, array $params): array
    {
        if (empty($params['client_id']) || empty($params['client_secret'])) {
            throw new InvalidArgumentException('clientId and clientSecret are required for http basic auth');
        }

        $encodedCredentials = urlencode(
            base64_encode(sprintf('%s:%s', $params['client_id'], $params['client_secret']))
        );
        unset($params['client_id'], $params['client_secret']);

        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $encodedCredentials,
                'User-Agent' => $this->userAgent,
            ]
        ];

        if ($method === AbstractProvider::METHOD_POST) {
            $options['body'] = $this->getAccessTokenBody($params);
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $params
     * @return string
     */
    protected function getAccessTokenBody(array $params): string
    {
        $json = json_encode($params);
        if ($json === false) {
            throw new InvalidArgumentException('Unable to encode parameters to JSON');
        }
        return $json;
    }
}
