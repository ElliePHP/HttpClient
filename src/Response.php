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
}
