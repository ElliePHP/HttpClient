<?php

namespace ElliePHP\Components\HttpClient;

use Exception;
use Throwable;

/**
 * Exception thrown when an HTTP request fails.
 * 
 * This exception wraps errors that occur during HTTP requests,
 * including network failures, timeouts, and other transport-level errors.
 */
class RequestException extends Exception
{
    /**
     * Create a new RequestException.
     *
     * @param string $message The error message
     * @param int $code The error code (default: 0)
     * @param Throwable|null $previous The previous exception for exception chaining
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
