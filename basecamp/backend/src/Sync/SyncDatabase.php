<?php

namespace Costlocker\Integrations\Sync;

interface SyncDatabase
{
    /** @return \Costlocker\Integrations\Entities\CostlockerCompany */
    public function findCompanyByWebhook($webhookUrl);

    /** @return \Costlocker\Integrations\Entities\BasecampProject */
    public function findByCostlockerId($id);

    /** @return \Costlocker\Integrations\Entities\BasecampProject */
    public function findByBasecampId($id);

    /** @return \Costlocker\Integrations\Entities\BasecampProject */
    public function upsertProject($costockerProjectId, array $update);
}
