<?php

namespace Costlocker\Integrations\Sync;

use Symfony\Component\HttpFoundation\ParameterBag;
use Costlocker\Integrations\Entities\CostlockerUser;

class SyncProjectToBasecamp
{
    private $synchronizer;

    public function __construct(Synchronizer $s)
    {
        $this->synchronizer = $s;
    }

    public function __invoke(array $jsonRequest, CostlockerUser $user = null)
    {
        $json = new ParameterBag($jsonRequest);

        $config = new SyncRequest();
        $config->account = $json->get('account');
        $config->costlockerProject = $json->get('costlockerProject');
        $isProjectLinked = $json->get('mode') == 'add';
        $config->updatedBasecampProject = $isProjectLinked ? $json->get('basecampProject') : null;
        $config->basecampClassicCompanyId = $json->get('basecampClassicCompanyId');
        $config->areTodosEnabled = $json->get('areTodosEnabled');
        if ($config->areTodosEnabled) {
            $config->isDeletingTodosEnabled = $json->get('isDeletingTodosEnabled');
            $config->isRevokeAccessEnabled = $json->get('isRevokeAccessEnabled');
        }

        $r = SyncProjectRequest::completeSynchronization($user);

        return [$this->synchronizer->__invoke($r, $config)];
    }
}
