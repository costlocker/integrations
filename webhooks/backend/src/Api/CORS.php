<?php

namespace Costlocker\Integrations\Api;

/**
 * @SuppressWarnings(PHPMD.Superglobals)
 * @SuppressWarnings(PHPMD.ExitExpression)
 */
class CORS
{
    public static function enable($domain)
    {
        if ($domain) {
            header("Access-Control-Allow-Origin: {$domain}");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN");
            header("Access-Control-Allow-Expose-Headers: Location,Content-Disposition,Content-Length,Pragma,Expires");
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
    }
}
