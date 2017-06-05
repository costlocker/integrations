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

    public function __invoke($accountId): BasecampApi
    {
        $account = $this->getAccount($accountId);

        $api = new Connect(new BasecampClient);
        $api->init(
            $this->user->getBasecampAccessToken(),
            $account['href'],
            $account['product']
        );

        return $api;
    }

    public function getAccount($accountId)
    {
        return $this->user->getBasecampAccount($accountId);
    }
}
