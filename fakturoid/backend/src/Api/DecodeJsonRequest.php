<?php

namespace Costlocker\Integrations\Api;

use Symfony\Component\HttpFoundation\Request;

class DecodeJsonRequest
{
    public function __invoke(Request $r)
    {
        if (0 === strpos($r->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($r->getContent(), true);
            $r->request->replace(is_array($data) ? $data : array());
        }
    }
}
