<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RedirectToApp
{
    private $session;
    private $appUrl;

    public function __construct(SessionInterface $s, $appUrl)
    {
        $this->session = $s;
        $this->appUrl = $appUrl;
    }

    public function loadInvoiceFromRequest(Request $r)
    {
        if (!$this->session->get('fakturoid') && $r->query->get('project') && $r->query->get('invoice')) {
            $this->session->set('queryString', $r->getQueryString());
        }
    }

    public function goToInvoice()
    {
        $invoiceData = $this->session->remove('queryString');
        $queryString = $invoiceData ? "?{$invoiceData}" : '';
        return new RedirectResponse("{$this->appUrl}/invoice{$queryString}");
    }

    public function goToHomepage()
    {
        return new RedirectResponse($this->appUrl);
    }

    public function loginError($errorMessage)
    {
        return new RedirectResponse("{$this->appUrl}?loginError={$errorMessage}");
    }
}
