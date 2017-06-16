<?php

namespace Costlocker\Integrations\Basecamp\Api;

use Symfony\Component\Console\Output\ConsoleOutput;

class BasecampRemoteTest extends \PHPUnit_Framework_TestCase
{
    private $output;
    private $connect;

    public function setUp()
    {
        $this->output = new ConsoleOutput();
        $this->connect = new Connect(new BasecampClient);
        $this->initApiConnection();
    }

    private function initApiConnection()
    {
        $accessToken = getenv('accessToken');
        $accountUrl = getenv('accountUrl');
        $basecampVersion = getenv('accountVersion');
        $this->connect->init($accessToken, $accountUrl, $basecampVersion);

        if (!$accessToken || !$accountUrl || !$basecampVersion) {
            $this->writelnOnlyOnce(
                'Missing authorization (env variables in phpunit.xml)', [
                'accessToken' => $accessToken,
                'accountUrl' => $accountUrl,
                'accountVersion' => $basecampVersion,
                ]
            );
            $this->markTestSkipped();
        }
    }

    public function testLoadInformationFromApi()
    {
        $projects = $this->connect->getProjects();
        $this->writeln('Projects', $projects);
        $bcProjectId = null;

        foreach ($projects as $bcProject) {
            $bcProjectId = $bcProject->id;
            assertThat($this->connect->projectExists($bcProject->id), is(true));
        }

        $people = $this->connect->getPeople($bcProjectId);
        $this->writeln('People', $people);

        $todolists = $this->connect->getTodolists($bcProjectId);
        $this->writeln('Todolists', $todolists);
    }

    public function testEditBasecampViaApi()
    {
        $testName = "Test - " . date('Y-m-d H:i:s');

        $bcProjectId = $this->connect->createProject($testName, null, 'test description');
        $this->writeln('Created project', $bcProjectId);

        $bcWebhookId = $this->connect->registerWebhook($bcProjectId, 'https://example.com/endpoint', true);
        $this->writeln('Created webhook', $bcWebhookId);

        $this->connect->registerWebhook($bcProjectId, 'https://example.com/endpoint', false, $bcWebhookId);
        $this->writeln('Deactivated webhook', $bcWebhookId);

        $bcTodolistId = $this->connect->createTodolist($bcProjectId, $testName);
        $this->writeln('Created todolist', $bcTodolistId);

        $bcPersonId = getenv('existingPersonInBasecamp');
        $grantedPeople = $this->connect->grantAccess($bcProjectId, ['John Doe' => getenv('newPersonEmail'), $bcPersonId]);
        $this->writeln('Granted people', $grantedPeople);

        $bcTodoitemId = $this->connect->createTodo($bcProjectId, $bcTodolistId, "{$testName} (complete)", $bcPersonId);
        $this->writeln('Created todos', $bcTodoitemId);

        $this->connect->completeTodo($bcProjectId, $bcTodoitemId);
        $this->writeln('Completed todos', $bcTodoitemId);

        $revokedPeople = $this->connect->revokeAccess($bcProjectId, $bcPersonId);
        $this->writeln('Revoked people', $revokedPeople);

        $this->connect->deleteTodo($bcProjectId, $bcTodoitemId);
        $this->writeln('Deleted todos', $bcTodoitemId);

        $this->connect->deleteTodolist($bcProjectId, $bcTodolistId);
        $this->writeln('Deleted todolist', $bcTodolistId);

        $this->connect->archiveProject($bcProjectId);
        $this->writeln('Archived project', $bcProjectId);
    }

    private function writelnOnlyOnce($title, $data)
    {
        if ($this->getTestResultObject()->count() == 1) {
            $this->writeln($title, $data);
        }
    }

    private function writeln($title, $data)
    {
        $this->output->writeln([
            "<info>{$title}</info>",
            json_encode($data, JSON_PRETTY_PRINT)
        ]);
    }

}
