<?php

namespace Costlocker\Integrations\Api;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConvertExceptionToJson
{
    public function __invoke(\Exception $e)
    {
        if ($e instanceof NotFoundHttpException) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        }
        return ResponseHelper::error('Internal Server Error', 500);
    }
}
