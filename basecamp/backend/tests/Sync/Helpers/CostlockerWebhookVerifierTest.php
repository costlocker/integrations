<?php

namespace Costlocker\Integrations\Sync;

class CostlockerWebhookVerifierTest extends \PHPUnit_Framework_TestCase
{
    private $secret = '123456';
    private $body = [
        'irrelevant json data',
    ];
    private $headers = [];

    public function testValidSignature()
    {
        $this->givenSignatureHeaders($this->secret, 'sha256');
        $this->signatureShouldBe(true);
    }

    public function testDifferentSecret()
    {
        $invalidSecret = 'invalid secret';
        $this->givenSignatureHeaders($invalidSecret);
        $this->signatureShouldBe(false);
    }

    public function testDifferentAlgorithm()
    {
        $this->givenSignatureHeaders($this->secret, 'sha1');
        $this->signatureShouldBe(false);
    }

    public function testInvalidHeaders()
    {
        $this->headers = [];
        $this->signatureShouldBe(false);
    }

    private function signatureShouldBe($expectedResult)
    {
        $request = [
            'rawBody' => json_encode($this->body),
            'headers' => $this->headers,
        ];
        $verifier = new CostlockerWebhookVerifier($this->secret);
        assertThat($verifier($request['rawBody'], $request['headers']), identicalTo($expectedResult));
    }

    private function givenSignatureHeaders($secret, $algorithm = 'sha256')
    {
        $this->headers = [
            'x-hook-signature' => ["{$algorithm}=" . hash_hmac('sha256', json_encode($this->body), $secret)],
        ];
    }
}
