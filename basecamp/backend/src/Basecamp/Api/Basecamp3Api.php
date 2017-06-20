<?php

namespace Costlocker\Integrations\Basecamp\Api;

class Basecamp3Api extends ExternalApi
{
    /** Helper for methods that converts API response to array */
    const DECODE_RESPONSE = true;

    private $todosetCache = [];

    protected function getHeaders($accessToken)
    {
        return array(
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json; charset=utf-8"
        );
    }

    public function getCompanies()
    {
        return array();
    }

    public function registerWebhook($bcProjectId, $webhookUrl, $isActive = true, $bcWebhookId = null)
    {
        $payload = [
            'payload_url' => $webhookUrl,
            'types' => ['Todo', 'Todolist'],
            'active' => $isActive,
        ];
        if ($bcWebhookId) {
            $response = $this->call('put', "/buckets/{$bcProjectId}/webhooks/{$bcWebhookId}.json", $payload);
        } else {
            $response = $this->call('post', "/buckets/{$bcProjectId}/webhooks.json", $payload);
        }
        return $this->getId($response);
    }

    public function getProjects()
    {
        $response = $this->call('get', '/projects.json', self::DECODE_RESPONSE);

        $projects = array();

        foreach ($response as $project) {
            if ($project->status == 'active') {
                $projects[] = (Object) array(
                    'id' => intval($project->id),
                    'name' => strval($project->name),
                );
            }
        }

        return $projects;
    }

    public function projectExists($bcProjectId)
    {
        $this->call('get', "/projects/{$bcProjectId}.json");

        return true;
    }

    public function archiveProject($bcProjectId)
    {
        $this->call('delete', "/projects/{$bcProjectId}.json");

        return true;
    }

    public function createProject($name, $bcCompanyId = null, $description = null)
    {
        $payloadData = array('name' => strval($name));

        if ($description !== null) {
            $payloadData['description'] = strval($description);
        }

        $response = $this->call('post', '/projects.json', $payloadData);

        return $this->getId($response);
    }

    public function getPeople($bcProjectId)
    {
        $responseItems = $this->call('get', "/projects/{$bcProjectId}/people.json", self::DECODE_RESPONSE);

        $people = array();

        if (is_array($responseItems)) {
            foreach ($responseItems as $person) {
                $people[strval($person->email_address)] = (Object) array(
                    'id' => intval($person->id),
                    'name' => strval($person->name),
                    'admin' => $person->admin,
                );
            }
        }

        return $people;
    }

    public function grantAccess($bcProjectId, $emails)
    {
        if (!(empty($emails))) {
            $accesses = [
                'grant' => [],
                'revoke' => [],
                'create' => [],
            ];
            foreach ($emails as $name => $email) {
                if (is_numeric($email)) {
                    $accesses['grant'][] = $email;
                } else {
                    $accesses['create'][] = array('name' => $name, 'email_address' => $email);
                }
            }
            $response = $this->call('put', "/projects/{$bcProjectId}/people/users.json", $accesses);
            return $this->decodeResponse($response);
        }
        return true;
    }

    public function revokeAccess($bcProjectId, $bcPersonId)
    {
        $this->call('put', "/projects/{$bcProjectId}/people/users.json", array('revoke' => array($bcPersonId)));

        return true;
    }

    public function getTodolists($bcProjectId)
    {
        $todoset = $this->findTodosetId($bcProjectId);
        $response = $this->call(
            'get',
            "/buckets/{$bcProjectId}/todosets/{$todoset}/todolists.json",
            self::DECODE_RESPONSE
        );
        $todolists = array();

        foreach ($response as $todolist) {
            $todoitems = array();
            $listId = $todolist->id;
            $todos = $this->call(
                'get',
                "/buckets/{$bcProjectId}/todolists/{$listId}/todos.json",
                self::DECODE_RESPONSE
            );

            foreach ($todos as $todoitem) {
                $assignee = array_shift($todoitem->assignees);
                $todoitems[intval($todoitem->id)] = (Object) array(
                    'content' => strval($todoitem->content),
                    'creator_id' => intval($todoitem->creator->id),
                    'creator_name' => strval($todoitem->creator->name),
                    'assignee_id' => $assignee ? intval($assignee->id) : null,
                    'assignee_name' => $assignee ? strval($assignee->name) : null,
                    'assignee' => $assignee ? $this->assigneeToPerson($assignee) : null,
                );
            }

            $todolists[intval($todolist->id)] = (Object) array(
                'name' => strval($todolist->name),
                'todoitems' => $todoitems,
            );
        }

        return $todolists;
    }

    private function assigneeToPerson(\stdClass $assignee)
    {
        $names = explode(' ', $assignee->name, 2) + ['', ''];
        return [
            'email' => $assignee->email_address,
            'first_name' => $names[0],
            'last_name' => $names[1],
        ];
    }

    public function createTodolist($bcProjectId, $name)
    {
        $todoset = $this->findTodosetId($bcProjectId);
        $response = $this->call(
            'post',
            "/buckets/{$bcProjectId}/todosets/{$todoset}/todolists.json",
            array('name' => strval($name))
        );

        return $this->getId($response);
    }

    private function findTodosetId($bcProjectId)
    {
        if (!isset($this->todosetCache[$bcProjectId])) {
            $project = $this->call('get', "/projects/{$bcProjectId}.json", self::DECODE_RESPONSE);

            foreach ($project->dock as $bucket) {
                if ($bucket->name == 'todoset') {
                    $this->todosetCache[$bcProjectId] = $bucket->id;
                    break;
                }
            }
        }

        return $this->todosetCache[$bcProjectId];
    }

    public function deleteTodolist($bcProjectId, $bcTodolistId)
    {
        $this->call('delete', "/buckets/{$bcProjectId}/todolists/{$bcTodolistId}.json");

        return true;
    }

    public function createTodo($bcProjectId, $bcTodolistId, $content, $assignee = null)
    {
        $payload = array('content' => strval($content));
        if (!is_null($assignee)) {
            $payload['assignee_ids'] = array(
                intval($assignee)
            );
        }

        $response = $this->call('post', "/buckets/{$bcProjectId}/todolists/{$bcTodolistId}/todos.json", $payload);

        return $this->getId($response);
    }

    public function completeTodo($bcProjectId, $bcTodoitemId)
    {
        $this->call('post', "/buckets/{$bcProjectId}/todos/{$bcTodoitemId}/completion.json");

        return true;
    }

    public function deleteTodo($bcProjectId, $bcTodoitemId)
    {
        $this->call('delete', "/buckets/{$bcProjectId}/todos/{$bcTodoitemId}.json");

        return true;
    }

    protected function parseIdFromResponse($response)
    {
        $createdItem = $this->decodeResponse($response);
        if (isset($createdItem->id)) {
            return intval($createdItem->id);
        }
    }

    /**
     * @param array $request
     * @return string
     */
    protected function encodeRequest($request)
    {
        return Json::encode($request);
    }

    protected function decodeResponse($response)
    {
        return Json::decode($response->body);
    }
}
