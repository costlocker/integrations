<?php

namespace Costlocker\Integrations\Basecamp\Api;

class BcxApi extends ExternalApi
{
    /** Helper for methods that converts API response to array */
    const DECODE_RESPONSE = true;
    
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

    public function getProjects()
    {
        $response = $this->call('get', '/projects.json', self::DECODE_RESPONSE);

        $projects = array();

        foreach ($response as $project) {
            if ($project->archived == false) {
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

        return TRUE;
    }

    public function getTodolists($bcProjectId)
    {
        $response = $this->call('get', "/projects/{$bcProjectId}/todolists.json", self::DECODE_RESPONSE);
        $todolists = array();

        foreach ($response as $todolist) {
            $todoitems = array();
            $listId = $todolist->id;
            $todos = $this->call('get', "/projects/{$bcProjectId}/todolists/{$listId}.json", self::DECODE_RESPONSE);

            foreach ($todos->todos->remaining as $todoitem) {
                $todoitems[intval($todoitem->id)] = (Object) array(
                    'content' => strval($todoitem->content),
                    'creator_id' => intval($todoitem->creator->id),
                    'creator_name' => strval($todoitem->creator->name),
                    'assignee_id' => (isset($todoitem->assignee->id) ? intval($todoitem->assignee->id) : NULL),
                    'assignee_name' => (isset($todoitem->assignee->name) ? strval($todoitem->assignee->name) : NULL),
                );
            }

            $todolists[intval($todolist->id)] = (Object) array(
                'name' => strval($todolist->name),
                'todoitems' => $todoitems,
            );
        }

        return $todolists;
    }

    public function archiveProject($bcProjectId)
    {
        $this->call('put', "/projects/{$bcProjectId}.json", array('archived' => true));

        return TRUE;
    }

    public function completeTodo($bcProjectId, $bcTodoitemId)
    {
        $this->call('put', "/projects/{$bcProjectId}/todos/{$bcTodoitemId}.json", array('completed' => true));

        return TRUE;
    }

    public function createProject($name, $bcCompanyId = NULL, $description = NULL)
    {
        $payloadData = array('name' => strval($name));

        if ($description !== NULL) {
            $payloadData['description'] = strval($description);
        }

        $response = $this->call('post', '/projects.json', $payloadData);

        return $this->getId($response);
    }

    public function getPeople($bcProjectId)
    {
        $responseItems = $this->call('get', "/projects/{$bcProjectId}/accesses.json", self::DECODE_RESPONSE);

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
            $this->call('post', "/projects/{$bcProjectId}/accesses.json", array('email_addresses' => $emails));
        }
        return TRUE;
    }

    public function revokeAccess($bcProjectId, $bcPersonId)
    {
        $this->call('delete', "/projects/{$bcProjectId}/accesses/{$bcPersonId}.json");

        return TRUE;
    }

    public function createTodolist($bcProjectId, $name)
    {
        $response = $this->call('post', "/projects/{$bcProjectId}/todolists.json", array('name' => strval($name)));

        return $this->getId($response);
    }

    public function createTodo($bcProjectId, $bcTodolistId, $content, $assignee = NULL)
    {
        $payload = array('content' => strval($content));
        if (!is_null($assignee)) {
            $payload['assignee'] = array(
                'id' => intval($assignee),
                'type' => 'Person',
            );
        }

        $response = $this->call('post', "/projects/{$bcProjectId}/todolists/{$bcTodolistId}/todos.json", $payload);

        return $this->getId($response);
    }

    public function deleteTodolist($bcProjectId, $bcTodolistId)
    {
        $this->call('delete', "/projects/{$bcProjectId}/todolists/{$bcTodolistId}.json");

        return TRUE;
    }

    public function deleteTodo($bcProjectId, $bcTodoitemId)
    {
        $this->call('delete', "/projects/{$bcProjectId}/todos/{$bcTodoitemId}.json");

        return TRUE;
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
