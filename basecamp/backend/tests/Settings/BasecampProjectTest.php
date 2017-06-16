<?php

namespace Costlocker\Integrations\Entities;

class BasecampProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testDetectNewValueDifferentThanDefault()
    {
        $project = new BasecampProject();
        $project->updateSettings(['isBasecampWebhookEnabled' => true]);
        assertThat($project->isNotChangedSetting('isBasecampWebhookEnabled'), is(false));
    }

    public function testDetectChangedValue()
    {
        $project = new BasecampProject();
        $project->updateSettings(['isBasecampWebhookEnabled' => false]);
        $project->updateSettings(['isBasecampWebhookEnabled' => true]);
        assertThat($project->isNotChangedSetting('isBasecampWebhookEnabled'), is(false));
    }

    public function testIgnoreNoChangeInValue()
    {
        $project = new BasecampProject();
        $project->updateSettings(['isBasecampWebhookEnabled' => false]);
        $project->updateSettings(['isBasecampWebhookEnabled' => false]);
        assertThat($project->isNotChangedSetting('isBasecampWebhookEnabled'), is(true));
    }
}
