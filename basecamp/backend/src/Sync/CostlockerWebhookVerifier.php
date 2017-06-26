<?php

namespace Costlocker\Integrations\Sync;

class CostlockerWebhookVerifier
{
    private $secret;
    private $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function __invoke($json, array $headers)
    {
        $signature = $headers['x-hook-signature'][0] ?? '=';
        $encodedBody = json_encode($json, $this->jsonFlags);
        list($algo, $hash) = explode('=', $signature, 2);

        if (!in_array($algo, hash_algos(), TRUE)) {
            return false;
        }

        $expectedHash = hash_hmac($algo, $encodedBody, $this->secret);
        return hash_equals($expectedHash, $hash);
    }
}
