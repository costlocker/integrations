<?php

namespace Costlocker\Integrations\Sync;

use Symfony\Component\HttpFoundation\ParameterBag;
use Costlocker\Integrations\Entities\CostlockerUser;

class ProcessManualRequest
{
    private $synchronizer;

    public function __construct(Synchronizer $s)
    {
        $this->synchronizer = $s;
    }

    public function __invoke(array $jsonRequest, CostlockerUser $user = null)
    {
        $json = new ParameterBag($jsonRequest);
        $ids = (array) $json->get('costlockerProject');

        $results = [];
        foreach ($ids as $id) {
            $request = SyncRequest::completeSynchronization($user);
            $request->costlockerId = $id;
            $request->account = $json->get('account');
            if (count($ids) == 1) {
                $isProjectLinked = $json->get('mode') == 'add';
                $request->updatedBasecampProject = $isProjectLinked ? $json->get('basecampProject') : null;
                $request->basecampClassicCompanyId = $json->get('basecampClassicCompanyId');
            }

            $request->areTodosEnabled = $json->get('areTodosEnabled');
            if ($request->areTodosEnabled) {
                $request->isDeletingTodosEnabled = $json->get('isDeletingTodosEnabled');
                $request->isRevokeAccessEnabled = $json->get('isRevokeAccessEnabled');
            }

            $request->areTasksEnabled = $json->get('areTasksEnabled');
            if ($request->areTasksEnabled) {
                $request->isDeletingTasksEnabled = $json->get('isDeletingTasksEnabled');
                $request->isCreatingActivitiesEnabled = $json->get('isCreatingActivitiesEnabled');
                $request->isDeletingActivitiesEnabled = $json->get('isDeletingActivitiesEnabled');
                $request->isBasecampWebhookEnabled = $json->get('isBasecampWebhookEnabled');
            }

            $results[] = $this->synchronizer->__invoke($request);
        }

        return $results;
    }
}
