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

    // costlocker settings
    public $areTodosEnabled = true;
    public $isDeletingTodosEnabled = true;
    public $isRevokeAccessEnabled = false;

    // basecamp settings
    public $areTasksEnabled = false;
    public $isDeletingTasksEnabled = false;
    public $isCreatingActivitiesEnabled = false;
    public $isDeletingActivitiesEnabled = false;
    public $isBasecampWebhookEnabled = false;

    public static function completeSynchronization(CostlockerUser $user = null)
    {
        $r = new SyncRequest();
        $r->isCompleteProjectSynchronized = true;
        $r->costlockerUser = $user;
        return $r;
    }

    public function isDeleteDisabled()
    {
        return !$this->isDeletingTodosEnabled && !$this->isRevokeAccessEnabled;
    }

    public function toSettings()
    {
        return [
            // costlocker -> basecamp
            'areTodosEnabled' => $this->areTodosEnabled,
            'isDeletingTodosEnabled' => $this->isDeletingTodosEnabled,
            'isRevokeAccessEnabled' => $this->isRevokeAccessEnabled,
            // basecamp -> costlocker
            'areTasksEnabled' => $this->areTasksEnabled,
            'isDeletingTasksEnabled' => $this->isDeletingTasksEnabled,
            'isCreatingActivitiesEnabled' => $this->isCreatingActivitiesEnabled,
            'isDeletingActivitiesEnabled' => $this->isDeletingActivitiesEnabled,
            'isBasecampWebhookEnabled' => $this->isBasecampWebhookEnabled,
        ];
    }
}
