<?php

namespace Tests\Basecamp;

use Mockery as m;
use Costlocker\Integrations\Basecamp\Api\Connect;
use Costlocker\Integrations\Basecamp\Api\BasecampClient;
use Costlocker\Integrations\Basecamp\Api\BasecampAccessException;

abstract class GivenBasecampConnect extends \PHPUnit_Framework_TestCase
{
    /** @var m\MockInterface */
    private $client;

    /** @var Connect */
    protected $api;

    /** @var ApiCall */
    private $call;

    /** @var ApiCall[] */
    private $calls = [];

    protected $project = 'irrelevant project';
    protected $id = 'irrelevant id';

    public function setUp()
    {
        $this->client = m::mock(BasecampClient::class);
        $this->api = new Connect($this->client);
        $this->api->init('irrelevant token', 'irrelevant url', $this->getApiType());
    }

    abstract protected function getApiType();

    abstract protected function getResponseType();

    abstract protected function getCreateResponseWithId($id);

    public function testGetActiveProjects()
    {
        $this->whenApiReturns('projects');
        $projects = $this->api->getProjects();
        assertThat($projects, arrayWithSize(1));
        assertThat($projects, is([
            (object) [
                'id' => 2,
                'name' => 'Development'
            ]
        ]));
        $this->call->urlContains("/projects.{$this->getResponseType()}");
    }

    public function testGetPeople()
    {
        $this->whenApiReturns('people');
        $this->assertEquals(
            $this->api->getPeople($this->project),
            [
                'johndoe@example.com' => (object) [
                    'id' => 1,
                    'name' => 'John Doe',
                    'admin' => false,
                ]
            ]
        );
    }

    public function testReturnTrueWhenProjectExists()
    {
        $this->givenApiCall();
        assertThat($this->api->projectExists($this->project), is(true));
    }

    public function testSendAuthAndContentTypeInHeaders()
    {
        $this->spyApiCall('get');
        $this->api->projectExists($this->project);
        $this->call->headersContains('Authorization: Bearer ');
        $this->call->headersContains("Content-Type: application/{$this->getResponseType()}");
    }

    public function testBubbleExceptionFromClientWhenSomethingWentWrong()
    {
        $this->givenApiCall()->andThrow(BasecampAccessException::class);
        $this->setExpectedException(BasecampAccessException::class);
        $this->api->projectExists($this->project);
    }

    protected function whenApiReturns()
    {
        foreach (func_get_args() as $file) {
            $this->spyApiCall(
                'get',
                $this->givenClientResponse($file)
            );
        }
    }

    protected function whenEntityIsCreated($executeCreateMethod, $rawResponse = null)
    {
        $response = $rawResponse ? : $this->getCreateResponseWithId(123);
        $this->spyApiCall('post', (object) $response);
        assertThat($executeCreateMethod(), identicalTo(123));
    }

    protected function whenEntitiesAreModified()
    {
        $this->spyApiCall('post');
    }

    protected function whenEntityIsUpdated($returnedResponse = null)
    {
        $response = $returnedResponse ? $this->givenClientResponse($returnedResponse) : null;
        $this->spyApiCall('put', $response);
    }

    protected function whenEntityIsDeleted()
    {
        $this->spyApiCall('delete');
    }

    private function spyApiCall($method, $returnValue = null)
    {
        return $this->givenApiCall($method)->andReturnUsing(function () use ($returnValue) {
            $this->calls[] = $this->call = ApiCall::fromClientCall(func_get_args());
            return $returnValue;
        });
    }

    protected function givenClientResponse($file)
    {
        return (object) [
            'body' => file_get_contents(
                __DIR__ . "/fixtures/{$this->getApiType()}/{$file}.{$this->getResponseType()}"
            )
        ];
    }

    private function givenApiCall($method = 'get')
    {
        return $this->client->shouldReceive($method)->once();
    }

    protected function assertApiWasNotCalled()
    {
        assertThat($this->call, is(nullValue()));
    }

    protected function assertCalledUrl()
    {
        foreach (func_get_args() as $index => $url) {
            $this->calls[$index]->urlContains($url);
        }
    }

    protected function assertRequestContains($expectedText)
    {
        $this->call->requestContains($expectedText);
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
