<?php

namespace Costlocker\Integrations\Fakturoid;

use Costlocker\Integrations\Auth\GetUser;

class GetSubjects
{
    private $getUser;
    private $downloadSubjects;

    public function __construct(GetUser $u, DownloadSubjects $d)
    {
        $this->getUser = $u;
        $this->downloadSubjects = $d;
    }

    public function __invoke()
    {
        $account = $this->getUser->getFakturoidAccount();
        if (!$account->getSubjects()) {
            $this->downloadSubjects->__invoke($account);
        }
        return $this->convertSubjects($account->getSubjects());
    }

    private function convertSubjects(array $apiSubjects)
    {
        $subjects = [];
        foreach ($apiSubjects as $subject) {
            if ($subject['type'] != 'supplier') {
                $subjects[] = [
                    'id' => $subject['id'],
                    'name' => $subject['name'],
                    'has_vat' => $subject['vat_no'] ? true : false,
                ];
            }
        }
        return $subjects;
    }
}
