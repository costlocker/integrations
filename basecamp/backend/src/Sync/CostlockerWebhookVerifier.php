<?php

namespace Costlocker\Integrations\Sync;

class CostlockerWebhookVerifier
{
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function __invoke($rawBody, array $headers)
    {
        $signature = $headers['x-hook-signature'][0] ?? '=';
        list($algo, $hash) = explode('=', $signature, 2);

        if (!in_array($algo, hash_algos(), true)) {
            return false;
        }

        $expectedHash = hash_hmac($algo, $rawBody, $this->secret);
        return hash_equals($expectedHash, $hash);
    }
}
