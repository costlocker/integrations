<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;

class GetDataFromHarvest
{
    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $strategy = null;
        if ($r->query->get('billing')) {
            $strategy = new GetBilling();
        } elseif ($r->query->get('expenses')) {
            $strategy = new GetExpenses();
        } elseif ($r->query->get('peoplecosts')) {
            $strategy = new GetPeopleCosts();
        } else {
            $strategy = new GetProjects();
        }
        return $strategy($r, $apiClient);
    }
}
