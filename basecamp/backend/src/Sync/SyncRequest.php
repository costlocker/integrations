<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\CostlockerUser;

class SyncRequest
{
    // costlocker
    /** @var int */
    public $costlockerId;
    /** @var array */
    public $projectItems;
    /** @var CostlockerUser */
    public $costlockerUser;
    /** @var bool */
    public $isCompleteProjectSynchronized;

    // basecamp mapping
    public $account;
    public $updatedBasecampProject;
    public $basecampClassicCompanyId;

    /** @var SyncSettings */
    public $settings;

    public static function completeSynchronization(CostlockerUser $user = null)
    {
        $r = new SyncRequest();
        $r->isCompleteProjectSynchronized = true;
        $r->costlockerUser = $user;
        return $r;
    }

    public function __construct()
    {
        $this->settings = new SyncSettings();
    }
}
