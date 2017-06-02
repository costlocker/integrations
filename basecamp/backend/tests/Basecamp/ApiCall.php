<?php

namespace Tests\Basecamp;

class ApiCall
{
    private $url;
    private $content;
    private $headers;

    public static function fromClientCall(array $args)
    {
        $call = new ApiCall();
        if (count($args) == 2) {
            list($call->url, $headers) = $args;
        } else {
            list($call->url, $call->content, $headers) = $args;
        }
        $call->headers = implode("\n", $headers);
        return $call;
    }

    public function requestContains($expectedText)
    {
        assertThat($this->content, containsString($expectedText));
    }

    public function urlContains($url)
    {
        assertThat($this->url, containsString($url));
    }

    public function headersContains($header)
    {
        assertThat($this->headers, containsString($header));
    }
}
