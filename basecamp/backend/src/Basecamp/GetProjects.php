<?php

namespace Costlocker\Integrations\Basecamp;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\BasecampUser;

class GetProjects
{
    private $basecamps;
    private $getUser;

    public function __construct(BasecampAdapter $b, GetUser $u)
    {
        $this->basecamps = $b;
        $this->getUser = $u;
    }

    public function __invoke(Request $r)
    {
        $costlockerUser = $this->getUser->getCostlockerUser();
        $account = $costlockerUser->getUser($r->query->get('account'));
        if ($account instanceof BasecampUser) {
            $client = $this->basecamps->buildClient($account->id);
            return [
                'projects' => $client->getProjects(),
                'companies' => $client->getCompanies(),
            ];
        } else {
            return [];
        }
    }
}
