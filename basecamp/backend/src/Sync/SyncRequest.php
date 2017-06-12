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

    public function isDeleteDisabled()
    {
        return !$this->isDeletingTodosEnabled && !$this->isRevokeAccessEnabled;
    }

    public function toSettings()
    {
        return [
            'areTodosEnabled' => $this->areTodosEnabled,
            'isDeletingTodosEnabled' => $this->isDeletingTodosEnabled,
            'isRevokeAccessEnabled' => $this->isRevokeAccessEnabled,
        ];
    }
}
