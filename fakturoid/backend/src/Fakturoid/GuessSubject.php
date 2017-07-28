<?php

namespace Costlocker\Integrations\Fakturoid;

use Costlocker\Integrations\Auth\GetUser;

class GuessSubject
{
    private $getUser;

    public function __construct(GetUser $u)
    {
        $this->getUser = $u;
    }

    public function __invoke($costlockerClientName)
    {
        $account = $this->getUser->getFakturoidAccount();
        foreach ($account->getSubjects() as $subject) {
            if ($this->isMatchingName($costlockerClientName, $subject['name'])) {
                return $subject['id'];
            }
        }
        return null;
    }

    private function isMatchingName($a, $b)
    {
        return mb_strtolower($a, 'utf-8') == mb_strtolower($b, 'utf-8');
    }
}
