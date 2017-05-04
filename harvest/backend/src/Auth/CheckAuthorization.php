<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\Api\ResponseHelper;

class CheckAuthorization
{
    private $session;
    private $service;

    public static function harvest(SessionInterface $s)
    {
        return new self($s, 'harvest');
    }

    public static function costlocker(SessionInterface $s)
    {
        return new self($s, 'costlocker');
    }

    private function __construct(SessionInterface $s, $service)
    {
        $this->session = $s;
        $this->service = $service;
    }

    public function __invoke()
    {
        if (!$this->session->get($this->service)) {
            return ResponseHelper::error("Unathorized in {$this->service}", 401);
        }
    }
}
