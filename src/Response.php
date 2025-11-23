<?php

namespace ElliePHP\Components\HttpClient;

use JsonException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Response - Wrapper around Symfony ResponseInterface with convenience methods
 */
class Response
{
    private ?string $cachedContent = null;
    private ?array $cachedJson = null;

    public function __construct(
        private readonly ResponseInterface $response
    ) {
    }

    /**
     * Get the response body as a string
     *
     * Returns the complete raw response body. The content is cached
     * after the first call to avoid multiple reads.
     *
     * @return string The raw response body
     */
    public function body(): string
    {
        if ($this->cachedContent === null) {
            $this->cachedContent = $this->response->getContent(false);
        }
        return $this->cachedContent;
    }

    /**
     * Get the HTTP status code
     * 
     * Returns the HTTP status code from the response (e.g., 200, 404, 500).
     * 
     * @return int The HTTP status code
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Check if the response has a successful status code (2xx)
     * 
     * Returns true if the HTTP status code is in the 200-299 range,
     * indicating a successful request.
     * 
     * @return bool True if status is 2xx, false otherwise
     */
    public function successful(): bool
    {
        $status = $this->status();
        return $status >= 200 && $status < 300;
    }

    /**
     * Check if the response has a successful status code (2xx)
     * 
     * Alias for successful(). Returns true if the HTTP status code
     * is in the 200-299 range, indicating a successful request.
     * 
     * @return bool True if status is 2xx, false otherwise
     */
    public function success(): bool
    {
        return $this->successful();
    }

    /**
     * Check if the response has a failed status code (4xx or 5xx)
     * 
     * Returns true if the HTTP status code is in the 400-599 range,
     * indicating a client error (4xx) or server error (5xx).
     * 
     * @return bool True if status is 4xx or 5xx, false otherwise
     */
    public function failed(): bool
    {
        $status = $this->status();
        return $status >= 400 && $status < 600;
    }

    /**
     * Check if the response has an error status code (4xx or 5xx)
     * 
     * Alias for failed(). Returns true if the HTTP status code is in
     * the 400-599 range, indicating a client error (4xx) or server error (5xx).
     * 
     * @return bool True if status is 4xx or 5xx, false otherwise
     */
    public function isError(): bool
    {
        return $this->failed();
    }

    /**
     * Check if the response has a client error status code (4xx)
     * 
     * Returns true if the HTTP status code is in the 400-499 range,
     * indicating a client error (e.g., 400 Bad Request, 404 Not Found).
     * 
     * @return bool True if status is 4xx, false otherwise
     */
    public function isClientError(): bool
    {
        $status = $this->status();
        return $status >= 400 && $status < 500;
    }

    /**
     * Check if the response has a server error status code (5xx)
     * 
     * Returns true if the HTTP status code is in the 500-599 range,
     * indicating a server error (e.g., 500 Internal Server Error, 503 Service Unavailable).
     * 
     * @return bool True if status is 5xx, false otherwise
     */
    public function isServerError(): bool
    {
        $status = $this->status();
        return $status >= 500 && $status < 600;
    }

    /**
     * Check if the response has a redirect status code (3xx)
     * 
     * Returns true if the HTTP status code is in the 300-399 range,
     * indicating a redirect (e.g., 301 Moved Permanently, 302 Found).
     * 
     * @return bool True if status is 3xx, false otherwise
     */
    public function isRedirect(): bool
    {
        $status = $this->status();
        return $status >= 300 && $status < 400;
    }

    /**
     * Check if the response status code is 200 OK
     * 
     * @return bool True if status is 200, false otherwise
     */
    public function isOk(): bool
    {
        return $this->status() === 200;
    }

    /**
     * Check if the response status code is 201 Created
     * 
     * @return bool True if status is 201, false otherwise
     */
    public function isCreated(): bool
    {
        return $this->status() === 201;
    }

    /**
     * Check if the response status code is 204 No Content
     * 
     * @return bool True if status is 204, false otherwise
     */
    public function isNoContent(): bool
    {
        return $this->status() === 204;
    }

    /**
     * Check if the response status code is 400 Bad Request
     * 
     * @return bool True if status is 400, false otherwise
     */
    public function isBadRequest(): bool
    {
        return $this->status() === 400;
    }

    /**
     * Check if the response status code is 401 Unauthorized
     * 
     * @return bool True if status is 401, false otherwise
     */
    public function isUnauthorized(): bool
    {
        return $this->status() === 401;
    }

    /**
     * Check if the response status code is 403 Forbidden
     * 
     * @return bool True if status is 403, false otherwise
     */
    public function isForbidden(): bool
    {
        return $this->status() === 403;
    }

    /**
     * Check if the response status code is 404 Not Found
     * 
     * @return bool True if status is 404, false otherwise
     */
    public function isNotFound(): bool
    {
        return $this->status() === 404;
    }

    /**
     * Check if the response status code is 422 Unprocessable Entity
     * 
     * @return bool True if status is 422, false otherwise
     */
    public function isUnprocessableEntity(): bool
    {
        return $this->status() === 422;
    }

    /**
     * Check if the response status code is 429 Too Many Requests
     * 
     * @return bool True if status is 429, false otherwise
     */
    public function isTooManyRequests(): bool
    {
        return $this->status() === 429;
    }

    /**
     * Check if the response status code is 500 Internal Server Error
     * 
     * @return bool True if status is 500, false otherwise
     */
    public function isInternalServerError(): bool
    {
        return $this->status() === 500;
    }

    /**
     * Get all response headers
     * 
     * Returns an associative array of all response headers.
     * Header names are keys, and values are arrays of header values.
     * 
     * @return array Associative array of headers
     */
    public function headers(): array
    {
        return $this->response->getHeaders(false);
    }

    /**
     * Get a specific response header
     * 
     * Returns the value of a specific header by name. Header name
     * matching is case-insensitive. If the header has multiple values,
     * only the first value is returned.
     * 
     * @param string $name The header name (case-insensitive)
     * @return string|null The header value, or null if not found
     */
    public function header(string $name): ?string
    {
        $headers = $this->headers();
        $lowerName = strtolower($name);
        
        foreach ($headers as $key => $values) {
            if (strtolower($key) === $lowerName) {
                return is_array($values) ? ($values[0] ?? null) : $values;
            }
        }
        
        return null;
    }

    /**
     * Decode JSON response with optional key access
     * 
     * Decodes the response body as JSON and returns the result as an array.
     * If a key is provided, returns the value at that key in the decoded JSON.
     * The decoded JSON is cached after the first call.
     * 
     * Returns null if:
     * - The response body is not valid JSON
     * - A key is provided but doesn't exist in the decoded JSON
     * 
     * Example usage:
     * ```php
     * $response = $client->get('https://api.example.com/user');
     * $data = $response->json();           // Get entire decoded array
     * $name = $response->json('name');     // Get specific key value
     * ```
     * 
     * @param string|null $key Optional key to access in the decoded JSON
     * @return mixed The decoded JSON array, or value at key, or null on failure
     */
    public function json(?string $key = null): mixed
    {
        if ($this->cachedJson === null) {
            try {
                $content = $this->body();
                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                $this->cachedJson = is_array($decoded) ? $decoded : [];
            } catch (JsonException) {
                return null;
            }
        }

        if ($key === null) {
            return $this->cachedJson;
        }

        return $this->cachedJson[$key] ?? null;
    }

    /**
     * Throw an exception if the response indicates a failure
     * 
     * Throws a RequestException if the response has a failed status code (4xx or 5xx).
     * Returns the response instance for method chaining if successful.
     * 
     * Example:
     * ```php
     * $response = Http::get('https://api.example.com/users');
     * $response->throw(); // Throws if 4xx or 5xx
     * $data = $response->json();
     * ```
     * 
     * @return $this Returns this response instance for method chaining
     * @throws RequestException If the response has a failed status code
     */
    public function throw(): self
    {
        if ($this->failed()) {
            $status = $this->status();
            $message = "HTTP request returned status code {$status}";
            
            // Try to get error message from JSON response
            $body = $this->body();
            if (!empty($body)) {
                $json = $this->json();
                if (is_array($json) && isset($json['message'])) {
                    $message = $json['message'];
                } elseif (is_array($json) && isset($json['error'])) {
                    $message = $json['error'];
                }
            }
            
            throw new RequestException($message, $status);
        }
        
        return $this;
    }

    /**
     * Get JSON response or throw an exception if the request failed
     * 
     * Returns the decoded JSON response if successful, or throws a RequestException
     * if the response has a failed status code (4xx or 5xx).
     * 
     * Example:
     * ```php
     * // This will throw if the response is not successful
     * $data = Http::get('https://api.example.com/users')
     *     ->jsonOrFail();
     * ```
     * 
     * @param string|null $key Optional key to access in the decoded JSON
     * @return mixed The decoded JSON array, or value at key
     * @throws RequestException If the response has a failed status code
     */
    public function jsonOrFail(?string $key = null): mixed
    {
        $this->throw();
        return $this->json($key);
    }
}
