<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Request;

class ProcessCostlockerRedirect
{
    private $redirectToApp;
    private $checkAuth;

    public function __construct(RedirectToApp $r, CheckAuthorization $a)
    {
        $this->redirectToApp = $r;
        $this->checkAuth = $a;
    }

    public function __invoke(Request $r)
    {
        if ($this->checkAuth->checkAccount('costlocker')) {
            $this->redirectToApp->loadInvoiceFromRequest($r);
            return $this->redirectToApp->goToCostlockerLogin();
        }
        return $this->redirectToApp->goToInvoice("?{$r->getQueryString()}");
    }
}
