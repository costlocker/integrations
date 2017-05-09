<?php

namespace Costlocker\Integrations\Auth\Provider;

class HarvestOAuthProvider extends \League\OAuth2\Client\Provider\GenericProvider
{
    public static function buildFromEnv()
    {
        $host = getenv('HARVEST_HOST');
        return new self([
            'clientId' => getenv('HARVEST_CLIENT_ID'),
            'clientSecret' => getenv('HARVEST_CLIENT_SECRET'),
            'redirectUri' => getenv('HARVEST_REDIRECT_URL'),
            'urlAuthorize' => "{$host}/oauth2/authorize",
            'urlAccessToken' => "{$host}/oauth2/token",
            'urlResourceOwnerDetails' => "{$host}/account/who_am_i",
        ]);
    }

    protected function getAuthorizationHeaders($token = null)
    {
        return parent::getAuthorizationHeaders($token) + [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }
}
