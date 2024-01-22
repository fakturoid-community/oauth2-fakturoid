# Sign in with Fakturoid Provider for OAuth 2.0 Client

<p align=center>
  <a href="https://github.com/fakturoid-community/oauth2-fakturoid/actions"><img src="https://badgen.net/github/checks/fakturoid-community/oauth2-fakturoid/master"></a>
  <a href="https://packagist.org/packages/fakturoid-community/oauth2-fakturoid"><img src="https://badgen.net/packagist/dm/fakturoid-community/oauth2-fakturoid"></a>
  <a href="https://packagist.org/packages/fakturoid-community/oauth2-fakturoid"><img src="https://badgen.net/packagist/v/fakturoid-community/oauth2-fakturoid"></a>
  <a href="https://packagist.org/packages/fakturoid-community/oauth2-fakturoid"><img src="https://badgen.net/packagist/php/fakturoid-community/oauth2-fakturoid"></a>
  <a href="https://github.com/fakturoid-community/oauth2-fakturoid"><img src="https://badgen.net/github/license/fakturoid-community/oauth2-fakturoid"></a>

</p>
This package provides Fakturoid OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require fakturoid-community/oauth2-fakturoid
```

## Usage

Usage is the same as The League's OAuth client, using `\Fakturoid\Oauth2\Provider\FakturoidProvider` as the provider.

### Authorization Code Flow

```php

$provider = new Fakturoid\Oauth2\Provider\FakturoidProvider([
    'clientId' => '{fakturoid-client-id}',
    'clientSecret' => '{fakturoid-client-secret}', 
    'redirectUri' => 'https://example.com/callback-url',
    'userAgent' => 'Corp (mail@corp.me)'
]);

if (!isset($_POST['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_POST['state']) || ($_POST['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    /** @var AccessToken $token */
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_POST['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    // Important: The most details are only visible in the very first login!
    // In the second and third and ... ones you'll only get the identifier of the user!
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getFullName());

    } catch (Exception $e) {

        // Failed to get user details
        exit(':-(');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```


### Client Credentials Flow

```php

$provider = new Fakturoid\Oauth2\Provider\FakturoidProvider([
    // get from Settings → User account
    'clientId' => '{fakturoid-client-id}',
    'clientSecret' => '{fakturoid-client-secret}', 
    'userAgent' => 'Corp (mail@corp.me)'
]);

// Try to get an access token (using the authorization code grant)
/** @var AccessToken $token */
$token = $provider->getAccessToken('client_credentials');

// Optional: Now you have a token you can look up a users profile data
// Important: The most details are only visible in the very first login!
// In the second and third and ... ones you'll only get the identifier of the user!
try {

    // We got an access token, let's now get the user's details
    $user = $provider->getResourceOwner($token);

    // Use these details to create a new profile
    printf('Hello %s!', $user->getFullName());

} catch (Exception $e) {

    // Failed to get user details
    exit(':-(');
}

// Use this to interact with an API on the users behalf
echo $token->getToken();
```

### Refresh Tokens

If your access token expires you can refresh them with the refresh token.

```
$refreshToken = $token->getRefreshToken();
$refreshTokenExpiration = $token->getRefreshTokenExpires();
```

## Testing

``` bash
$ composer test:phpunit
```