<?php

namespace Costlocker\Integrations\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseHelper
{
    public static function error($error, $statusCode = 400)
    {
        return new JsonResponse(['errors' => [$error]], $statusCode);
    }
}
