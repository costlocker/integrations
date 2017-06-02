<?php

namespace Costlocker\Integrations\Basecamp\Api;

class ClassicApi extends ExternalApi
{
    protected function getHeaders($accessToken)
    {
        return array(
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/xml; charset=utf-8",
            "Accept: application/xml"
        );
    }

    public function getCompanies()
    {
        $response = $this->call('get', '/companies.xml', 'company');

        $companies = array();

        foreach ($response as $company) {
            $companies[] = (Object) array(
                'id' => intval($company->id),
                'name' => strval($company->name),
            );
        }

        return $companies;
    }

    public function getProjects()
    {
        $response = $this->call('get', '/projects.xml', 'project');

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
        $this->call('get', "/projects/{$bcProjectId}.xml");

        return TRUE;
    }

    public function getTodolists($bcProjectId)
    {
        $todolistArray = $this->call('get', "/projects/{$bcProjectId}/todo_lists.xml", 'todo-list');

        $todolists = array();

        foreach ($todolistArray as $todolist) {
            $todoitems = array();

            $todoitemsArray = $this->call('get', "/todo_lists/{$todolist->id}/todo_items.xml", 'todo-item');

            foreach ($todoitemsArray as $todoitem) {
                $todoitem = (array) $todoitem;
                $todoitems[intval($todoitem['id'])] = (Object) array(
                        'content' => strval($todoitem['content']),
                        'creator_id' => intval($todoitem['creator-id']),
                        'creator_name' => strval($todoitem['creator-name']),
                        'assignee_id' => NULL,
                        'assignee_name' => NULL,
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
        $archiveProjectXML = new \SimpleXMLElement("<project></project>");
        $archiveProjectXML->addChild('status', 'archived');

        $this->call('put', "/projects/{$bcProjectId}.xml", $archiveProjectXML);

        return TRUE;
    }

    public function completeTodo($bcProjectId, $bcTodoitemId)
    {
        $this->call('put', "/todo_items/{$bcTodoitemId}/complete.xml");

        return TRUE;
    }

    public function createProject($name, $bcCompanyId = NULL, $description = NULL)
    {
        $createProjectXML = new \SimpleXMLElement("<request></request>");
        $project = $createProjectXML->addChild('project');
        $project->addChild('name', strval($name));

        // Add company ID to the request if provided
        if (!is_null($bcCompanyId)) {
            $project->addChild('company-id', $bcCompanyId);
        }

        $response = $this->call('post', '/projects.xml', $createProjectXML);

        return $this->getId($response);
    }

    public function getPeople($bcProjectId)
    {
        $response = $this->call('get', "/projects/{$bcProjectId}/people.xml", 'person');

        $people = array();

        foreach ($response as $person) {
            $person = (array) $person;
            $people[strval($person['email-address'])] = (Object) array(
                    'id' => intval($person['id']),
                    'name' => strval($person['first-name'] . " " . $person['last-name']),
                    'admin' => false,
            );
        }

        return $people;
    }

    public function grantAccess($bcProjectId, $emails)
    {
    }

    public function revokeAccess($bcProjectId, $bcPersonId)
    {
    }

    public function createTodolist($bcProjectId, $name)
    {
        $createListXML = new \SimpleXMLElement("<todo-list></todo-list>");
        $createListXML->addChild('name', strval($name));

        $response = $this->call('post', "/projects/{$bcProjectId}/todo_lists.xml", $createListXML);

        return $this->getId($response);
    }

    public function createTodo($bcProjectId, $bcTodolistId, $content, $assignee = NULL)
    {
        $createTodoXML = new \SimpleXMLElement("<todo-item></todo-item>");
        $createTodoXML->addChild('content', strval($content));
        if (!is_null($assignee)) {
            $createTodoXML->addChild('responsible-party', $assignee);
        }

        $response = $this->call('post', "/todo_lists/{$bcTodolistId}/todo_items.xml", $createTodoXML);

        return $this->getId($response);
    }

    public function deleteTodolist($bcProjectId, $bcTodolistId)
    {
        $this->call('delete', "/todo_lists/{$bcTodolistId}.xml");

        return TRUE;
    }

    public function deleteTodo($bcProjectId, $bcTodoitemId)
    {
        $this->call('delete', "/todo_items/{$bcTodoitemId}.xml");

        return TRUE;
    }

    /**
     * @param \SimpleXMLElement $request
     * @return string
     */
    protected function encodeRequest($request)
    {
        return $request ? $request->asXML() : NULL;
    }

    /**
     * Converts XML string to array of elements and returns subarray of $root
     *
     * @param  object $response  XML string
     * @param  string $root Return tree starting at this element
     * @return array        Array of elements
     */
    protected function decodeResponse($response, $root = NULL)
    {
        $xml = $response->body;
        $processedArray = array();

        // Parse XML
        // Disable libxml error handling
        libxml_use_internal_errors(TRUE);
        $xmlObject = simplexml_load_string($xml);
        if ($xmlObject === FALSE) {
            throw new BasecampInvalidXmlException($xml);
        }

        // Convert to array
        // Dirty way to quickly convert SimpleXmlObject to an array
        $xmlArray = json_decode(json_encode($xmlObject), TRUE);
        unset($xmlArray['@attributes']);

        // Pokud XML obsahuje pouze jeden element, obsahuje simpleXMLobject pouze element.
        // Pokud je elementu vic, obsahuje pole elementu.
        // Zde se ujistime, ze tato metoda vraci *pole* elementu nezavisle na jejich poctu.

        if (isset($xmlArray[$root][0])) {
            foreach ($xmlArray[$root] as $element) {
                $processedArray[] = (object) $element;
            }
        } else {
            if (isset($xmlArray[$root])) {
                $processedArray[] = (object) $xmlArray[$root];
            } else {
                $processedArray = $xmlArray;
            }
        }

        return $processedArray;
    }

    protected function parseIdFromResponse($response)
    {
        if (isset($response->header)) {
            $parsedResponse = explode("\n", $response->header);
            foreach ($parsedResponse as $header) {
                if (is_numeric(strpos($header, 'Location'))) {
                    $createdItemId = preg_replace("/[^0-9]/", '', substr($header, strrpos($header, '/')));
                    return intval($createdItemId);
                }
            }
        }
    }
}
