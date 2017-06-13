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
        $r = SyncProjectRequest::completeSynchronization($user);

        $ids = (array) $json->get('costlockerProject');

        $results = [];
        foreach ($ids as $id) {
            $config = new SyncRequest();
            $config->account = $json->get('account');
            $config->costlockerProject = $id;
            if (count($ids) == 1) {
                $isProjectLinked = $json->get('mode') == 'add';
                $config->updatedBasecampProject = $isProjectLinked ? $json->get('basecampProject') : null;
                $config->basecampClassicCompanyId = $json->get('basecampClassicCompanyId');
            }
            $config->areTodosEnabled = $json->get('areTodosEnabled');
            if ($config->areTodosEnabled) {
                $config->isDeletingTodosEnabled = $json->get('isDeletingTodosEnabled');
                $config->isRevokeAccessEnabled = $json->get('isRevokeAccessEnabled');
            }
            $results[] = $this->synchronizer->__invoke($r, $config);
        }

        return $results;
    }
}
