<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Database\CostlockerCompany;

class RegisterWebhook
{
    private $client;
    private $webhookUrl;

    public function __construct(CostlockerClient $c, $webhookUrl)
    {
        $this->client = $c;
        $this->webhookUrl = $webhookUrl;
    }

    public function __invoke(CostlockerCompany $company)
    {
        if ($company->urlWebhook && $this->exists($company->urlWebhook)) {
            return;
        }

        $response = $this->client->__invoke(
            '/webhooks',
            [
                'url' => $this->webhookUrl,
                'events' => ['peoplecosts.change'],
            ]
        );
        $json = json_decode($response->getBody(), true);
        $company->urlWebhook = $json['data'][0]['links']['webhook'];
    }

    private function exists($webhookUrl)
    {
        return $this->client->__invoke($webhookUrl)->getStatusCode() == 200;
    }
}
