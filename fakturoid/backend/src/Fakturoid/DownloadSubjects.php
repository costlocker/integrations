<?php

namespace Costlocker\Integrations\Fakturoid;

use Costlocker\Integrations\FakturoidClient;
use Costlocker\Integrations\Entities\FakturoidAccount;
use Costlocker\Integrations\Database\Database;

class DownloadSubjects
{
    private $client;
    private $database;

    public function __construct(FakturoidClient $c, Database $db)
    {
        $this->client = $c;
        $this->database = $db;
    }

    public function __invoke(FakturoidAccount $account)
    {
        $params = $account->subjectsDownloadedAt
            ? ['updated_since' => $account->subjectsDownloadedAt->format('c')]
            : [];

        $page = 1;
        $hasNextPage = true;
        while ($hasNextPage) {
            $params['page'] = $page;
            $response = $this->client->__invoke(
                "/accounts/{$account->slug}/subjects.json?" . http_build_query($params)
            );
            if ($response->getStatusCode() != 200) {
                return;
            }
            $downloadedSubjects = json_decode($response->getBody(), true);
            $account->addSubjects($downloadedSubjects);

            $page++;
            $hasNextPage = is_int(strpos($response->getHeaderLine('Link'), "?page={$page}"));
        }

        $account->subjectsDownloadedAt = new \DateTime();
        $this->database->persist($account);
    }
}
