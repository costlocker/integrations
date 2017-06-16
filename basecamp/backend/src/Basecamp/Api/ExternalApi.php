<?php

namespace Costlocker\Integrations\Basecamp\Api;

abstract class ExternalApi implements BasecampApi
{
    private $callApi;

    public function __construct(BasecampClient $client, $baseUrl, $accessToken)
    {
        $headers = $this->getHeaders($accessToken);
        $this->callApi = function ($method, $path, $params = NULL) use ($client, $baseUrl, $headers) {
            $url = $baseUrl . $path;
            if (in_array($method, ['get', 'delete'])) {
                $response = $client->{$method}($url, $headers);
                return $params ? $this->decodeResponse($response, $params) : $response;
            } else {
                return $client->{$method}($url, $this->encodeRequest($params), $headers);
            }
        };
    }

    /**
     * @return \stdClass
     */
    protected function call($method, $endpoint, $params = NULL)
    {
        return $this->callApi->__invoke($method, $endpoint, $params);
    }

    public function canBeSynchronizedFromBasecamp()
    {
    }

    public function registerWebhook($bcProjectId, $webhookUrl, $isActive = true, $bcWebhookId = null)
    {
        throw new BasecampInvalidCallException('Not supported');
    }

    public function buildProjectUrl($accountDetails, $projectId)
    {
    }

    /**
     * @return array
     */
    abstract protected function getHeaders($accessToken);

    /**
     * @return string
     */
    abstract protected function encodeRequest($request);

    /**
     * @return array
     */
    abstract protected function decodeResponse($response);

    /**
     * @param  object $response Response to parse
     * @return integer          ID of a resource
     * @throws BasecampMissingReturnValueException
     */
    protected function getId($response)
    {
        $id = $this->parseIdFromResponse($response);
        if (is_int($id)) {
            return $id;
        }
        throw new BasecampMissingReturnValueException();
    }

    /**
     * Gets ID of a Basecamp resource from Location header in HTTP response
     * Used by Create methods of Classic Basecamp API.
     *
     * Pri uspesnem vytvoreni noveho obsahu vraci novy Basecamp nove vytvoreny
     * objekt v tele odpovedi. Basecamp Classic vraci Id tohoto objektu pouze
     * v hlavicce odpovedi v hodnote Location, odkud ho tato metoda vyfiltruje.
     *
     * @param  object $response Response to parse
     * @return integer          ID of a resource
     */
    abstract protected function parseIdFromResponse($response);
}
