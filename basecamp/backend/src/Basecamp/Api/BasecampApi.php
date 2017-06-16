<?php

namespace Costlocker\Integrations\Basecamp\Api;

interface BasecampApi
{
    /**
     * Is supported = hasWebhooks && detailedInformationAboutAssignee
     * @return bool
     */
    public function canBeSynchronizedFromBasecamp();

    public function registerWebhook($bcProjectId, $webhookUrl, $isActive = true, $bcWebhookId = null);

    /**
     *
     * Produces a list of companies (clients). Basecamp Classic only
     *
     * Structure of return value:
     *
     * Array
     * (
     *   [0] => stdClass Object
     *     (
     *       [id] => 111111
     *       [name] => <company_name>
     *     )
     *   ...
     * )
     *
     * @return array List of ids and names of available companies
     */
    public function getCompanies();

    /**
     *
     * Returns list of projects visible to authorized user
     *
     * Structure of return value:
     *
     * Array
     * (
     *   [0] => stdClass Object
     *     (
     *       [id] => 111111
     *       [name] => <project_name>
     *     )
     *   ...
     * )
     *
     * @return array List of ids and names of active projects
     */
    public function getProjects();

    /**
     * @param stdClass $accountDetails row from identity table
     * @return string
     */
    public function buildProjectUrl($accountDetails, $projectId);

    /**
     *
     * Checks if project exists in Basecamp
     *
     * @param  integer $bcProjectId Project ID
     * @return true
     */
    public function projectExists($bcProjectId);

    /**
     *
     * Archives a project in Basecamp
     *
     * @param  integer $bcProjectId Project ID
     * @return true
     */
    public function archiveProject($bcProjectId);

    /**
     *
     * Creates new project and returns its ID
     *
     * @param  string       $name        Name for the project
     * @param  NULL|integer $bcCompanyId Company (client) ID
     * @param  string $description       New Basecamp only
     * @return integer                   Project ID
     */
    public function createProject($name, $bcCompanyId = NULL, $description = NULL);

    /**
     *
     * Returns a list of people with access to a project
     *
     * Structure of return value:
     *
     * Array
     * (
     *   [<person_email>] => stdClass Object
     *     (
     *       [id] => <person_id>
     *       [name] => <person_name>
     *     )
     *   ...
     * )
     *
     * @param  integer $bcProjectId Project ID
     * @return array                List of IDs, names and emails
     */
    public function getPeople($bcProjectId);

    /**
     *
     * Grants access to project to people provided (New Basecamp only)
     *
     * @param  integer $bcProjectId Project ID
     * @param  array   $emails      Array of emails to grant access to
     * @return true
     */
    public function grantAccess($bcProjectId, $emails);

    /**
     *
     * Removes access to project from a person (New Basecamp only)
     *
     * @param  integer $bcProjectId Project ID
     * @param  integer $bcPersonId  Person ID
     * @return true
     */
    public function revokeAccess($bcProjectId, $bcPersonId);

    /**
     *
     * Returns list of todolists with attached todo items
     *
     * Structure of return value:
     *
     * Array
     * (
     *   [<todolist_id>] => stdClass Object
     *     (
     *       [name] => <todolist_name>
     *       [todoitems] => Array
     *       (
     *         [<todoitem_id>] => stdClass Object
     *           (
     *             [content] => <todoitem_content>
     *             [creator_id] => <creator_id>
     *             [creator_name] => <creator_name>
     *             [assignee_id] => <assignee_id> | NULL
     *             [assignee_name] => <assignee_name> | NULL
     *           )
     *         ...
     *       )
     *     )
     *   ...
     * )
     *
     * @param  integer $bcProjectId Project ID
     * @return array                Tree of todolists and todo items
     */
    public function getTodolists($bcProjectId);

    /**
     *
     * Creates new todolist in project
     *
     * @param  integer $bcProjectId Project ID
     * @param  string  $name        Name for the todolist
     * @return integer              Todolist ID
     */
    public function createTodolist($bcProjectId, $name);

    /**
     *
     * Removes todolist from project
     *
     * @param  integer $bcProjectId  Project ID
     * @param  integer $bcTodolistId Todolist ID
     * @return true
     */
    public function deleteTodolist($bcProjectId, $bcTodolistId);

    /**
     *
     * Creates new todo item in project and todolist
     *
     * @param  integer      $bcProjectId  Project ID
     * @param  integer      $bcTodolistId ID of parent Basecamp todo list
     * @param  string       $content      Content of the todo item
     * @param  NULL|integer $assignee     ID of a person assigned
     * @return integer                    Todo item ID
     */
    public function createTodo($bcProjectId, $bcTodolistId, $content, $assignee = NULL);

    /**
     *
     * Marks single todo item as completed
     *
     * @param  integer $bcProjectId  Project ID
     * @param  integer $bcTodoitemId Todo item ID
     * @return true
     */
    public function completeTodo($bcProjectId, $bcTodoitemId);

    /**
     *
     * Removes todo item from project and todolist
     *
     * @param  integer $bcProjectId  Project ID
     * @param  integer $bcTodoitemId Todo item ID
     * @return true
     */
    public function deleteTodo($bcProjectId, $bcTodoitemId);
}
