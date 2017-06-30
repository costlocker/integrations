<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectToApp
{
    private $appUrl;

    public function __construct($appUrl)
    {
        $this->appUrl = $appUrl;
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
