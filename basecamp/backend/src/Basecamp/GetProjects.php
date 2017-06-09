<?php

namespace Costlocker\Integrations\Basecamp;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\BasecampUser;

class GetProjects
{
    private $basecampFactory;
    private $getUser;

    public function __construct(BasecampFactory $b, GetUser $u)
    {
        $this->basecampFactory = $b;
        $this->getUser = $u;
    }

    public function __invoke(Request $r)
    {
        $costlockerUser = $this->getUser->getCostlockerUser();
        $account = $costlockerUser->getUser($r->query->get('account'));
        if ($account instanceof BasecampUser) {
            $basecamp = $this->basecampFactory->__invoke($account->id);
            return [
                'projects' => $basecamp->getProjects(),
                'companies' => $basecamp->getCompanies(),
            ];
        } else {
            return [];
        }
    }
}
