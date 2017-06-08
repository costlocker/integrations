<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Basecamp\Api\Connect;
use Costlocker\Integrations\Basecamp\Api\BasecampClient;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;

class BasecampFactory
{
    private $user;
    private $accountId;

    public function __construct(GetUser $u)
    {
        $this->user = $u;
    }

    public function __invoke($accountId): BasecampApi
    {
        $this->accountId = $accountId;
        $account = $this->user->getBasecampAccount($this->accountId);

        $api = new Connect(new BasecampClient);
        $api->init(
            $this->user->getBasecampAccessToken($this->accountId),
            $account->urlApi,
            $account->product
        );

        return $api;
    }

    public function getAccount()
    {
        return [
            'id' => $this->accountId,
        ];
    }
}
