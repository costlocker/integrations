<?php

namespace Costlocker\Integrations\Basecamp;

use Symfony\Component\HttpFoundation\Request;

class GetProjects
{
    public function __invoke(Request $r)
    {
        if ($r->query->get('account') == 1234) {
            return [
                [
                    'id' => -1,
                    'name' => 'dummy',
                ]
            ];
        }
        return [
            [
                'id' => 123,
                'name' => 'testststs',
            ]
        ];
    }
}
