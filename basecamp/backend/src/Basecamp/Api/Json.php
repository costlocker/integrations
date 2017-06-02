<?php

namespace Costlocker\Integrations\Basecamp\Api;

/**
 * Json Exception
 */
class JsonException extends \Exception
{
}

/**
 * Json encode / decode wrapper
 * Based on Nette and http://phpfashion.com/how-to-encode-and-decode-json-in-php
 */
class Json
{
    /** @var array */
    private static $messages = array(
        JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Syntax error, malformed JSON',
        JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
        JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
    );

    public static function encode($value)
    {
        // needed to receive 'Invalid UTF-8 sequence' error; PHP bugs #52397, #54109, #63004
        if (function_exists('ini_set')) { // ini_set is disabled on some hosts :-(
            $old = ini_set('display_errors', 0);
        }

        // needed to receive 'recursion detected' error
        set_error_handler(function($severity, $message) {
            restore_error_handler();
            throw new JsonException($message);
        });

        $json = json_encode($value);

        restore_error_handler();
        if (isset($old)) {
            ini_set('display_errors', $old);
        }
        if ($error = json_last_error()) {
            $message = isset(static::$messages[$error]) ? static::$messages[$error] : 'Unknown error';
            throw new JsonException($message, $error);
        }

        return $json;
    }

    public static function decode($json, $assoc = FALSE)
    {
        $json = (string) $json;

        $value = json_decode($json, $assoc);

        if ($value === NULL && $json !== '' && strcasecmp($json, 'null')) { // '' do not clean json_last_error
            $error = PHP_VERSION_ID >= 50300 ? json_last_error() : 0;
            throw new JsonException(isset(static::$messages[$error]) ? static::$messages[$error] : 'Unknown error', $error);
        }

        return $value;
    }
}
