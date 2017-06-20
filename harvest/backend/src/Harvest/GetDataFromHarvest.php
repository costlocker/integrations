<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;
use Costlocker\Integrations\Sync\ImportDatabase;
use Costlocker\Integrations\Auth\GetUser;

class GetDataFromHarvest
{
    private $database;
    private $getUser;

    public function __construct(ImportDatabase $d, GetUser $u)
    {
        $this->database = $d;
        $this->getUser = $u;
    }

    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $strategy = null;
        if ($r->query->get('expenses')) {
            $strategy = new GetExpenses();
        } elseif ($r->query->get('peoplecosts')) {
            $strategy = new GetPeopleCosts();
        } else {
            $strategy = new GetProjects($this->database, $this->getUser);
        }
        return $strategy($r, $apiClient);
    }
}
