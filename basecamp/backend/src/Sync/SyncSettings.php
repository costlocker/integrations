<?php

namespace Costlocker\Integrations\Sync;

class SyncSettings
{
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

    private $previousSettings;

    public function __construct(array $settings = null)
    {
        $this->update($settings);
    }

    public function update(array $settings = null)
    {
        $this->previousSettings = $this->toArray();
        $this->loadFromArray($settings, array_keys($this->toArray()));
    }

    public function isNotChangedSetting($setting)
    {
        $old = $this->previousSettings[$setting] ?? null;
        $new = $this->toArray()[$setting] ?? null;
        return $old === $new;
    }

    public function loadCostlockerSettings(array $settings = null)
    {
        $options = [
            'areTodosEnabled', 'isDeletingTodosEnabled', 'isRevokeAccessEnabled',
        ];
        $this->loadFromArray($settings, $options);
    }

    public function loadBasecampSettings(array $settings = null)
    {
        $options = [
            'areTasksEnabled', 'isDeletingTasksEnabled', 'isCreatingActivitiesEnabled',
            'isDeletingActivitiesEnabled', 'isBasecampWebhookEnabled'
        ];
        $this->loadFromArray($settings, $options);
    }

    private function loadFromArray(array $rawSettings = null, array $options = [])
    {
        $settings = $rawSettings ?: [];
        foreach ($options as $option) {
            if (array_key_exists($option, $settings)) {
                $this->{$option} = $settings[$option];
            }
        }
    }

    public function disableUpdatingCostlocker()
    {
        $this->areTasksEnabled = false;
    }

    public function disableUpdatingBasecamp()
    {
        $this->areTodosEnabled = false;
    }

    public function isDeleteDisabledInCostlocker()
    {
        return !$this->isDeletingTodosEnabled && !$this->isRevokeAccessEnabled;
    }

    public function toArray()
    {
        return [
            'areTodosEnabled' => $this->areTodosEnabled,
            'isDeletingTodosEnabled' => $this->isDeletingTodosEnabled,
            'isRevokeAccessEnabled' => $this->isRevokeAccessEnabled,

            'areTasksEnabled' => $this->areTasksEnabled,
            'isDeletingTasksEnabled' => $this->isDeletingTasksEnabled,
            'isCreatingActivitiesEnabled' => $this->isCreatingActivitiesEnabled,
            'isDeletingActivitiesEnabled' => $this->isDeletingActivitiesEnabled,
            'isBasecampWebhookEnabled' => $this->isBasecampWebhookEnabled,
        ];
    }
}
