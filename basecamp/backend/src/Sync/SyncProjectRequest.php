<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\CostlockerUser;

class SyncProjectRequest
{
    /** @var int */
    public $costlockerId;
    /** @var array */
    public $projectItems;
    /** @var CostlockerUser */
    public $costlockerUser;
    /** @var bool */
    public $isCompleteProjectSynchronized;
    /** @var callable */
    public $createProject;

    public static function completeSynchronization(CostlockerUser $user = null)
    {
        $r = new SyncProjectRequest();
        $r->isCompleteProjectSynchronized = true;
        $r->costlockerUser = $user;
        return $r;
    }
}
