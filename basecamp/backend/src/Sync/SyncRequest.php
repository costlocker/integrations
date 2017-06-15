<?php

namespace Costlocker\Integrations\Sync;

class SyncRequest
{
    public $costlockerProject;
    public $account;
    public $updatedBasecampProject;
    public $basecampClassicCompanyId;

    public $areTodosEnabled = true;
    public $isDeletingTodosEnabled = true;
    public $isRevokeAccessEnabled = false;

    public $areTasksEnabled = false;
    public $isDeletingTasksEnabled = false;
    public $isCreatingActivitiesEnabled = false;
    public $isDeletingActivitiesEnabled = false;
    public $isBasecampWebhookEnabled = false;

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
