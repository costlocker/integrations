<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RedirectToApp
{
    private $session;
    private $appUrl;
    private $loginUrl;

    public function __construct(SessionInterface $s, $appUrl, $oauthLoginUrl)
    {
        $this->session = $s;
        $this->appUrl = $appUrl;
        $this->loginUrl = $oauthLoginUrl;
    }

    public function loadInvoiceFromRequest(Request $r)
    {
        if (!$this->session->get('fakturoid') && $r->query->get('project') && $r->query->get('billing')) {
            $this->session->set('queryString', $r->getQueryString());
        }
    }

    public function goToInvoice($requestQueryString = '')
    {
        $invoiceData = $this->session->remove('queryString');
        $queryString = $invoiceData ? "?{$invoiceData}" : $requestQueryString;
        return new RedirectResponse("{$this->appUrl}/invoice{$queryString}");
    }

    public function goToHomepage()
    {
        return new RedirectResponse($this->appUrl);
    }

    public function goToCostlockerLogin()
    {
        return new RedirectResponse($this->loginUrl);
    }

    public function loginError($errorMessage)
    {
        return new RedirectResponse("{$this->appUrl}?loginError={$errorMessage}");
    }
}
