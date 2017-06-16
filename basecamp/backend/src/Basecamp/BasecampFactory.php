<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Basecamp\Api\Connect;
use Costlocker\Integrations\Basecamp\Api\BasecampClient;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;
use Costlocker\Integrations\Entities\BasecampAccount;
use Costlocker\Integrations\Entities\BasecampProject;

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

    public function canBeSynchronizedFromBasecamp(BasecampAccount $account)
    {
        return $this->fakeApi($account)->canBeSynchronizedFromBasecamp();
    }

    public function buildProjectUrl(BasecampProject $p)
    {
        $account = $p->basecampUser->basecampAccount;
        return $this->fakeApi($account)->buildProjectUrl(
            (object) [
                'bc__product_type' => $account->product,
                'bc__account_href' => $account->urlApi,
                'bc__account_id' => $account->id,
            ],
            $p->basecampProject
        );
    }

    private function fakeApi(BasecampAccount $account)
    {
        $api = new Connect(new BasecampClient);
        $api->init(
            null,
            $account->urlApi,
            $account->product
        );
        return $api;
    }
}
