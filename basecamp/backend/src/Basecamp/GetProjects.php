<?php

namespace Costlocker\Integrations\Basecamp;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Basecamp\Api\Connect;
use Costlocker\Integrations\Basecamp\Api\BasecampClient;

class GetProjects
{
    private $user;

    public function __construct(GetUser $u)
    {
        $this->user = $u;
    }

    public function __invoke(Request $r)
    {
        $accountId = $r->query->get('account');
        $account = $this->user->getBasecampAccount($accountId);

        $api = new Connect(new BasecampClient);
        $api->init(
            $this->user->getBasecampAccessToken(),
            $account['href'],
            $account['product']
        );

        return $api->getProjects();
    }
}
