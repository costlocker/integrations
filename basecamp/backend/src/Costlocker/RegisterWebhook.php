<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\Event;

class RegisterWebhook
{
    private $client;
    private $logger;
    private $webhookUrl;

    public function __construct(CostlockerClient $c, EventsLogger $l, $webhookUrl)
    {
        $this->client = $c;
        $this->logger = $l;
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
                'events' => [
                    'projects.create',
                    'peoplecosts.change',
                ],
            ]
        );
        $json = json_decode($response->getBody(), true);
        $company->urlWebhook = $json['data'][0]['links']['webhook'];

        $this->logger->__invoke(
            Event::REGISTER_WEBHOOK,
            ['webhook' => $company->urlWebhook, 'company' => $company->id]
        );
    }

    private function exists($webhookUrl)
    {
        return $this->client->__invoke($webhookUrl)->getStatusCode() == 200;
    }
}
