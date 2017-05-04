<?php

// http://oauth2-client.thephpleague.com/usage/
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$costlockerHost = getenv('CL_HOST');
$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId' => getenv('CL_CLIENT_ID'),
    'clientSecret' => getenv('CL_CLIENT_SECRET'),
    'redirectUri' => null,
    'urlAuthorize' => "{$costlockerHost}/api-public/oauth2/authorize",
    'urlAccessToken' => "{$costlockerHost}/api-public/oauth2/access_token",
    'urlResourceOwnerDetails' => "{$costlockerHost}/api-public/v2/me",
]);
    
session_start();
echo "<pre>";

// If we don't have an authorization code then get one
if (!isset($_GET['code']) && !isset($_GET['error'])) {

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} elseif (isset($_GET['error'])) {

    echo json_encode($_GET, JSON_PRETTY_PRINT);
    exit;

} else {

    try {

        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo json_encode($accessToken->jsonSerialize(), JSON_PRETTY_PRINT);

        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $provider->getResourceOwner($accessToken);

        echo json_encode($resourceOwner->toArray(), JSON_PRETTY_PRINT);

    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

        // Failed to get the access token or user details.
        exit($e->getMessage());

    }

}