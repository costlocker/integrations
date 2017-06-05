<?php

namespace Costlocker\Integrations\Basecamp;

class SyncRequest
{
    public $costlockerProject;
    public $account;
    public $updatedBasecampProject;

    public $isDeletingTodosEnabled = false;
    public $isRevokeAccessEnabled = false;

    public function isDeleteDisabled()
    {
        return !$this->isDeletingTodosEnabled && !$this->isRevokeAccessEnabled;
    }
}
