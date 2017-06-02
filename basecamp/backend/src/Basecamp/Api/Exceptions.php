<?php

namespace Costlocker\Integrations\Basecamp\Api;

/**
 * Basecamp exception
 */
class BasecampException extends \Exception {}

/**
 * Basecamp authorization process failed.
 */
class BasecampAuthorizationException extends BasecampException {}

/**
 * Number of requests exceeded.
 */
class BasecampRateLimitException extends BasecampException {}

/**
 * Basecamp not responding.
 */
class BasecampUnavailableException extends BasecampException {}

/**
 * Missing permissions to perfom action or query a resource.
 */
class BasecampAccessException extends BasecampException {}

/**
 * General error.
 */
class BasecampGeneralException extends BasecampException {}

/**
 * cURL client exception.
 */
class BasecampClientException extends BasecampException {}

/**
 * Invalid XML encountered.
 */
class BasecampInvalidXmlException extends BasecampException {}

/**
 * Missing parameter.
 */
class BasecampMissingParameterException extends BasecampException {}

/**
 * Missing ID of a newly created resource in the return value. 
 */
class BasecampMissingReturnValueException extends BasecampException {}

/**
 * When calling BCX specific method while connected to Basecamp Classic.
 */
class BasecampInvalidCallException extends BasecampException {}

/**
 * Basecamp Auth not initialized
 */
class BasecampAuthNotInitializedException extends BasecampException {}

/**
 * Basecamp Connect not initialized
 */
class BasecampConnectNotInitializedException extends BasecampException {}
