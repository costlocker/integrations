<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LogoutUser
{
    private $session;

    public function __construct(SessionInterface $s)
    {
        $this->session = $s;
    }

    public function __invoke()
    {
        $this->session->clear();
    }
}
