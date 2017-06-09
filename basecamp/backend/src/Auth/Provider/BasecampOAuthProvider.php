<?php

namespace Costlocker\Integrations\Auth\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;

// Refresh token is not supported in https://github.com/14four/oauth2-basecamp
class BasecampOAuthProvider extends \FourteenFour\BasecampAuth\Provider\Basecamp
{
    public function getAccessToken($grant, array $options = [])
    {
        $defaultOptiosn = [
            'type' => $grant == 'refresh_token' ? 'refresh' : 'web_server'
        ];

        $newOptions = array_merge($defaultOptiosn, $options);

        return AbstractProvider::getAccessToken($grant, $newOptions);
    }
}
