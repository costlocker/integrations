<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;

class GetExpenses
{
    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $dateStart = $r->query->get('from', date('Y0101'));
        $dateEnd = $r->query->get('to', date('Ymd'));
        $expenses = $apiClient("/projects/{$r->query->get('expenses')}/expenses?from={$dateStart}&to={$dateEnd}");
        $categories = [];
        foreach ($apiClient("/expense_categories") as $client) {
            $categories[$client['expense_category']['id']] = $client['expense_category']['name'];
        }
        return array_map(
            function (array $expense) use ($categories) {
                return [
                    'id' => $expense['expense']['id'],
                    'description' => $this->expenseToDescription($expense, $categories),
                    'purchased' => [
                        'total_amount' => $expense['expense']['total_cost'],
                        'date' => $expense['expense']['spent_at'],
                    ],
                    'billed' => [
                        'total_amount' => $expense['expense']['billable'] ? $expense['expense']['total_cost'] : 0,
                    ],
                ];
            },
            $expenses
        );
    }

    private function expenseToDescription(array $expense, array $categories)
    {
        return "{$expense['expense']['units']}x " .
            $categories[$expense['expense']['expense_category_id']] .
            ($expense['expense']['notes'] ? " ({$expense['expense']['notes']})" : '');
    }
}
