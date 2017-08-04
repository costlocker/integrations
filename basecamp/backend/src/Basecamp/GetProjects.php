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
            try {
                $client = $this->basecamps->buildClient($account->id);
                return [
                    'isAvailable' => true,
                    'projects' => $client->getProjects(),
                    'companies' => $client->getCompanies(),
                ];
            } catch (\Exception $e) {
                return [
                    'isAvailable' => false,
                    'projects' => [],
                    'companies' => [],
                ];
            }
        } else {
            return [];
        }
    }
}
