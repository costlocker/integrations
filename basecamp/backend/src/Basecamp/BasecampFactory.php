<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Basecamp\Api\Connect;
use Costlocker\Integrations\Basecamp\Api\BasecampClient;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;

class BasecampFactory
{
    private $user;

    public function __construct(GetUser $u)
    {
        $this->user = $u;
    }

    public function __invoke($basecampUserId): BasecampApi
    {
        $account = $this->user->getBasecampAccount($basecampUserId);

        $api = new Connect(new BasecampClient);
        $api->init(
            $this->user->getBasecampAccessToken($basecampUserId),
            $account->urlApi,
            $account->product
        );

        return $api;
    }
}
