<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FakeCostlockerAuthorization
{
    private $session;

    public function __construct(SessionInterface $s)
    {
        $this->session = $s;
    }

    public function __invoke()
    {
        if (self::isCostlockerDisabled()) {
            $fakeAccount = [
                'person' => [
                    'email' => null,
                    'first_name' => 'Anonymous',
                    'last_name' => 'User',
                ],
                'company' => [
                    'id' => null,
                    'name' => 'No company',
                ],
            ];
            return new JsonResponse([
                'harvest' => $this->session->get('harvest')['account'] ?? null,
                'costlocker' => $fakeAccount,
            ]);
        }
    }

    public static function isCostlockerDisabled()
    {
        return !getenv('CL_CLIENT_SECRET')
            && getenv('APP_IMPORT_DISABLED') == 'true';
    }
}
