<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LogoutUser
{
    private $session;
    private $appUrl;

    public function __construct(SessionInterface $s, $appUrl)
    {
        $this->session = $s;
        $this->appUrl = $appUrl;
    }

    public function __invoke()
    {
        $this->session->clear();
        return new RedirectResponse($this->appUrl);
    }
}
