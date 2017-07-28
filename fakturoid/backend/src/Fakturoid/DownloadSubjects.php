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
        $account->resetSubjects();

        $page = 1;
        $hasNextPage = true;
        while ($hasNextPage) {
            $response = $this->client->__invoke("/subjects.json?page={$page}");
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
