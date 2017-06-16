<?php

namespace Costlocker\Integrations\Basecamp\Api;

class Connect implements BasecampApi
{
    const BASECAMP_BCX_TYPE = 'bcx';
    const BASECAMP_CLASSIC_TYPE = 'basecamp';
    const BASECAMP_V3_TYPE = 'bc3';

    /** @var BasecampClient object */
    private $client;

    /** @var BasecampApi */
    private $currentApi;

    /**
     * @param BasecampClient $client
     */
    public function __construct(BasecampClient $client)
    {
        $this->client = $client;
    }

    /**
     * Initialize Basecamp Connect class with access token, URL for API requests
     * and Basecamp product type
     * @param  string $accessToken Basecamp access token
     * @param  string $accountUrl  Basecamp account API URL
     * @param  string $productType Basecamp product type (bcx or basecamp)
     */
    public function init($accessToken, $accountUrl, $productType)
    {
        if ($productType == Connect::BASECAMP_CLASSIC_TYPE) {
            $apiImplementation = ClassicApi::class;
        } elseif ($productType == Connect::BASECAMP_BCX_TYPE) {
            $apiImplementation = BcxApi::class;
        } else {
            $apiImplementation = Basecamp3Api::class;
        }
        $this->currentApi = new $apiImplementation($this->client, $accountUrl, $accessToken);
    }

    public function canBeSynchronizedFromBasecamp()
    {
        return $this->currentApi instanceof Basecamp3Api;
    }

    public function buildProjectUrl($accountDetails, $projectId)
    {
        if ($accountDetails->bc__product_type == Connect::BASECAMP_CLASSIC_TYPE) {
            return $accountDetails->bc__account_href . '/projects/' . $projectId;
        } elseif ($accountDetails->bc__product_type == Connect::BASECAMP_V3_TYPE) {
            return 'https://3.basecamp.com/' . $accountDetails->bc__account_id . '/projects/' . $projectId;
        } else {
            return 'https://basecamp.com/' . $accountDetails->bc__account_id . '/projects/' . $projectId;
        }
    }

    public function registerWebhook($bcProjectId, $webhookUrl, $isActive = true, $bcWebhookId = null)
    {
        return $this->currentApi->registerWebhook($bcProjectId, $webhookUrl, $isActive, $bcWebhookId);
    }

    public function getCompanies()
    {
        return $this->currentApi->getCompanies();
    }

    public function getProjects()
    {
        return $this->currentApi->getProjects();
    }

    public function projectExists($bcProjectId)
    {
        return $this->currentApi->projectExists($bcProjectId);
    }

    public function getTodolists($bcProjectId)
    {
        return $this->currentApi->getTodolists($bcProjectId);
    }

    public function archiveProject($bcProjectId)
    {
        return $this->currentApi->archiveProject($bcProjectId);
    }

    public function completeTodo($bcProjectId, $bcTodoitemId)
    {
        return $this->currentApi->completeTodo($bcProjectId, $bcTodoitemId);
    }

    public function createProject($name, $bcCompanyId = NULL, $description = NULL)
    {
        return $this->currentApi->createProject($name, $bcCompanyId, $description);
    }

    public function getPeople($bcProjectId)
    {
        return $this->currentApi->getPeople($bcProjectId);
    }

    public function grantAccess($bcProjectId, $emails)
    {
        return $this->currentApi->grantAccess($bcProjectId, $emails);
    }

    public function revokeAccess($bcProjectId, $bcPersonId)
    {
        return $this->currentApi->revokeAccess($bcProjectId, $bcPersonId);
    }

    public function createTodolist($bcProjectId, $name)
    {
        return $this->currentApi->createTodolist($bcProjectId, $name);
    }

    public function createTodo($bcProjectId, $bcTodolistId, $content, $assignee = NULL)
    {
        return $this->currentApi->createTodo($bcProjectId, $bcTodolistId, $content, $assignee);
    }

    public function deleteTodolist($bcProjectId, $bcTodolistId)
    {
        return $this->currentApi->deleteTodolist($bcProjectId, $bcTodolistId);
    }

    public function deleteTodo($bcProjectId, $bcTodoitemId)
    {
        return $this->currentApi->deleteTodo($bcProjectId, $bcTodoitemId);
    }
}
