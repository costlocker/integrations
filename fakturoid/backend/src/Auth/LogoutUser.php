<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LogoutUser
{
    private $session;
    private $redirectToApp;

    public function __construct(SessionInterface $s, RedirectToApp $r)
    {
        $this->session = $s;
        $this->redirectToApp = $r;
    }

    public function __invoke()
    {
        $this->session->clear();
        return $this->redirectToApp->goToHomepage();
    }
}
