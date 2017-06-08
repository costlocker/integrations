<?php

namespace Costlocker\Integrations\Sync;

class SyncProjectRequest
{
    /** @var int */
    public $costlockerId;
    /** @var array */
    public $projectItems;
    /** @var bool */
    public $isCompleteProjectSynchronized;
    /** @var callable */
    public $createProject;
}
