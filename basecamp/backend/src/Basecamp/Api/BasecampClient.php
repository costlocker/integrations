<?php

namespace Costlocker\Integrations\Basecamp\Api;

class BasecampClient
{
    /** HTTP 1.1 response codes */
    const
        S200_OK = 200,
        S201_CREATED = 201,
        S204_NO_CONTENT = 204,
        S300_MULTIPLE_CHOICES = 300,
        S301_MOVED_PERMANENTLY = 301,
        S302_FOUND = 302,
        S303_POST_GET = 303,
        S304_NOT_MODIFIED = 304,
        S307_TEMPORARY_REDIRECT = 307,
        S400_BAD_REQUEST = 400,
        S401_UNAUTHORIZED = 401,
        S403_FORBIDDEN = 403,
        S404_NOT_FOUND = 404,
        S405_METHOD_NOT_ALLOWED = 405,
        S410_GONE = 410,
        S415_UNSUPPORTED_MEDIA_TYPE = 415,
        S422_UNPROCESSABLE_ENTITY = 422,
        S429_TOO_MANY_REQUESTS = 429,
        S500_INTERNAL_SERVER_ERROR = 500,
        S501_NOT_IMPLEMENTED = 501,
        S502_BAD_GATEWAY = 502,
        S503_SERVICE_UNAVAILABLE = 503,
        S504_GATEWAY_TIMEOUT = 504;

    /** @var FALSE|cURL handle */
    private $curlSession = FALSE;

    /**
     * Default cURL options
     *
     * For simplicity the cURL connection is insecure. Proper way would be
     * to set the CURLOPT_CAINFO parameter with trusted CA certificate.
     *
     * @var array
     * @access private
     */
    private $curlOptions = array(
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => TRUE,
        //CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_USERAGENT => 'Costlocker (http://integrations.costlocker.com)',
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_SSL_VERIFYHOST => FALSE,
        CURLOPT_HEADER => TRUE,
    );

    public function __construct()
    {
        if (!in_array('curl', get_loaded_extensions())) {
            throw new BasecampClientException('PHP cURL extension not present, rebuild PHP with --with-curl to use cURL.');
        }
    }

    public function __destruct()
    {
        if ($this->curlSession !== FALSE) {
            curl_close($this->curlSession);
        }
    }

    /**
     * @param  string $url     URL to connect to
     * @param  array  $headers Request specific headers
     * @return object
     */
    public function get($url, $headers = array())
    {
        return $this->initialize($url)
                ->addHeaders($headers)
                ->setOption(CURLOPT_CUSTOMREQUEST, 'GET')
                ->setOption(CURLOPT_POST, FALSE)
                ->setOption(CURLOPT_POSTFIELDS, NULL)
                ->execute();
    }

    /**
     * @param  string $url     URL to connect to
     * @param  array  $params  POST parameters
     * @param  array  $headers Request specific headers
     * @return object
     */
    public function post($url, $params = array(), $headers = array())
    {
        if (is_array($params)) {
            $params = http_build_query($params);
        }

        return $this->initialize($url)
                ->addHeaders($headers)
                ->setOption(CURLOPT_CUSTOMREQUEST, 'POST')
                ->setOption(CURLOPT_POST, TRUE)
                ->setOption(CURLOPT_POSTFIELDS, $params)
                ->execute();
    }

    /**
     * @param  string $url     URL to connect to
     * @param  array  $params  PUT parameters
     * @param  array  $headers Request specific headers
     * @return object
     */
    public function put($url, $params = array(), $headers = array())
    {
        if (is_array($params)) {
            $params = http_build_query($params);
        }

        return $this->initialize($url)
                ->addHeaders($headers)
                ->setOption(CURLOPT_CUSTOMREQUEST, 'PUT')
                ->setOption(CURLOPT_POST, TRUE)
                ->setOption(CURLOPT_POSTFIELDS, $params)
                ->execute();
    }

    /**
     * @param  string $url     URL to connect to
     * @param  array  $headers Request specific headers
     * @return object
     */
    public function delete($url, $headers = array())
    {
        return $this->initialize($url)
                ->addHeaders($headers)
                ->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE')
                ->setOption(CURLOPT_POST, FALSE)
                ->setOption(CURLOPT_POSTFIELDS, NULL)
                ->execute();
    }

    /**
     * @param  string $name  Option name
     * @param  string $value Option value
     * @return object
     */
    private function setOption($name, $value)
    {
        curl_setopt($this->curlSession, $name, $value);
        return $this;
    }

    /**
     * @param  array $headers Array of strings
     * @return object
     */
    private function addHeaders($headers)
    {
        curl_setopt($this->curlSession, CURLOPT_HTTPHEADER, $headers);

        return $this;
    }

    /**
     * @param  string $url URL to connect to
     * @return object
     */
    private function initialize($url)
    {
        if ($this->curlSession === FALSE) {
            $this->curlSession = curl_init();
        }

        curl_setopt($this->curlSession, CURLOPT_URL, $url);
        curl_setopt_array($this->curlSession, $this->curlOptions);

        return $this;
    }

    /**
     * @return object
     */
    private function execute()
    {
        // Initialize return structure
        $result = (Object) array(
                'header' => '',
                'body' => '',
                'info' => '',
        );

        // Execute cURL request

        $response = curl_exec($this->curlSession);

        // Request failed

        if ($response === FALSE) {

            $curlError = curl_error($this->curlSession) . '(' . curl_errno($this->curlSession) . ')';
            throw new BasecampClientException($curlError);

            // Request successful
        } else {

            // Split the returned data into header and body

            list($result->header, $result->body) = explode("\r\n\r\n", $response, 2);

            // Get info about connection

            $result->info = curl_getinfo($this->curlSession);

            // Po uspesnem dotazu vraci Basecam 2xx HTTP status:
            // 200 OK - uspesny dotaz
            // 201 Created - pri vytvoreni obsahu
            // 204 No Content - pri mazani obsahu

            if ($result->info['http_code'] == self::S200_OK ||
                $result->info['http_code'] == self::S201_CREATED ||
                $result->info['http_code'] == self::S204_NO_CONTENT) {

                return $result;
            } else {

                // Error handling

                $errorDetails = '';
                if (is_numeric(strpos($result->info['content_type'], 'application/json'))) {
                    $moreInfo = Json::decode($result->body);
                    if (!(is_null($moreInfo))) {
                        $errorDetails = ' - ' . $moreInfo->error;
                    }
                }

                switch ($result->info['http_code']) {

                    case self::S400_BAD_REQUEST :
                        // Tato vyjimka muze nastat pokud chybi hlavicka dotazu
                        throw new BasecampGeneralException(sprintf('Bad request or missing headers' . $errorDetails));
                        break;
                    case self::S401_UNAUTHORIZED :
                        throw new BasecampAuthorizationException('Not authorized' . $errorDetails);
                        break;
                    case self::S403_FORBIDDEN :
                        throw new BasecampAccessException('Action not permitted' . $errorDetails);
                        break;
                    case self::S404_NOT_FOUND :
                        throw new BasecampAccessException('Resource not available' . $errorDetails);
                        break;
                    case self::S415_UNSUPPORTED_MEDIA_TYPE :
                        // Tato vyjimka muze nastat pokud dotaz neobsahuje hlavicku Content-type
                        throw new BasecampGeneralException('Wrong URL or missing Content-type in headers' . $errorDetails);
                        break;
                    case self::S422_UNPROCESSABLE_ENTITY :
                        throw new BasecampAccessException('Request was well-formed but was unable to be followed due to semantic errors' . $errorDetails);
                        break;
                    case self::S429_TOO_MANY_REQUESTS :
                        // Parse response headers for Retry-After value
                        $headers = explode("\n", $result->header);
                        foreach ($headers as $header) {
                            if (stripos($header, 'Retry-After:') !== FALSE) {
                                $retryAfter = $header;
                            }
                        }
                        throw new BasecampRateLimitException('Too many requests. ' . $retryAfter);
                        break;
                    case self::S500_INTERNAL_SERVER_ERROR :
                    case self::S501_NOT_IMPLEMENTED :
                    case self::S502_BAD_GATEWAY :
                    case self::S503_SERVICE_UNAVAILABLE :
                    case self::S504_GATEWAY_TIMEOUT :
                        throw new BasecampUnavailableException('Basecamp not available. HTTP code: ' . $result->info['http_code']);
                        break;
                    default :
                        throw new BasecampGeneralException('HTTP code: ' . $result->info['http_code']);
                }
            }
        }
    }
}
