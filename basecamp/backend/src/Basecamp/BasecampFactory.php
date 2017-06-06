<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Basecamp\Api\Connect;
use Costlocker\Integrations\Basecamp\Api\BasecampClient;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;

class BasecampFactory
{
    private $user;
    private $account;

    public function __construct(GetUser $u)
    {
        $this->user = $u;
    }

    public function __invoke($account): BasecampApi
    {
        $this->account = is_array($account) ? $account : $this->user->getBasecampAccount($account);

        $api = new Connect(new BasecampClient);
        $api->init(
            $this->user->getBasecampAccessToken(),
            $this->account['href'],
            $this->account['product']
        );

        return $api;
    }

    public function getAccount()
    {
        return $this->account;
    }
}
