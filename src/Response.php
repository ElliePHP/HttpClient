<?php

namespace ElliePHP\Components\HttpClient;

use JsonException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Response - Enhanced wrapper around Symfony ResponseInterface with convenience methods
 */
class Response
{
    private ?string $cachedContent = null;
    private mixed $cachedJson = null;
    private bool $jsonCached = false;

    public function __construct(
        private readonly ResponseInterface $response
    ) {
    }

    /**
     * Get the response body as a string
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
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Check if the response has a successful status code (2xx)
     */
    public function successful(): bool
    {
        $status = $this->status();
        return $status >= 200 && $status < 300;
    }

    /**
     * Alias for successful()
     */
    public function success(): bool
    {
        return $this->successful();
    }

    /**
     * Check if the response has a failed status code (4xx or 5xx)
     */
    public function failed(): bool
    {
        $status = $this->status();
        return $status >= 400 && $status < 600;
    }

    /**
     * Alias for failed()
     */
    public function isError(): bool
    {
        return $this->failed();
    }

    /**
     * Check if the response has a client error status code (4xx)
     */
    public function isClientError(): bool
    {
        $status = $this->status();
        return $status >= 400 && $status < 500;
    }

    /**
     * Check if the response has a server error status code (5xx)
     */
    public function isServerError(): bool
    {
        $status = $this->status();
        return $status >= 500 && $status < 600;
    }

    /**
     * Check if the response has a redirect status code (3xx)
     */
    public function isRedirect(): bool
    {
        $status = $this->status();
        return $status >= 300 && $status < 400;
    }

    // Specific status code helpers
    public function isOk(): bool { return $this->status() === 200; }
    public function isCreated(): bool { return $this->status() === 201; }
    public function isAccepted(): bool { return $this->status() === 202; }
    public function isNoContent(): bool { return $this->status() === 204; }
    public function isBadRequest(): bool { return $this->status() === 400; }
    public function isUnauthorized(): bool { return $this->status() === 401; }
    public function isForbidden(): bool { return $this->status() === 403; }
    public function isNotFound(): bool { return $this->status() === 404; }
    public function isUnprocessableEntity(): bool { return $this->status() === 422; }
    public function isTooManyRequests(): bool { return $this->status() === 429; }
    public function isInternalServerError(): bool { return $this->status() === 500; }
    public function isServiceUnavailable(): bool { return $this->status() === 503; }

    /**
     * Get all response headers
     */
    public function headers(): array
    {
        return $this->response->getHeaders(false);
    }

    /**
     * Get a specific response header (case-insensitive)
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
     * Check if a header exists
     */
    public function hasHeader(string $name): bool
    {
        return $this->header($name) !== null;
    }

    /**
     * Get all values for a specific header
     */
    public function headerValues(string $name): array
    {
        $headers = $this->headers();
        $lowerName = strtolower($name);

        foreach ($headers as $key => $values) {
            if (strtolower($key) === $lowerName) {
                return is_array($values) ? $values : [$values];
            }
        }

        return [];
    }

    /**
     * Decode JSON response with flexible key access
     *
     * @param string|null $key Dot notation supported (e.g., 'user.name')
     * @param mixed $default Default value if key not found
     * @param bool $isAssoc Whether to return associative array
     */
    public function json(?string $key = null, mixed $default = null, bool $isAssoc = true): mixed
    {
        if (!$this->jsonCached) {
            try {
                $content = $this->body();
                $this->cachedJson = json_decode($content, $isAssoc, 512, JSON_THROW_ON_ERROR);
                $this->jsonCached = true;
            } catch (JsonException) {
                $this->cachedJson = null;
                $this->jsonCached = true;
                return $default;
            }
        }

        if ($key === null) {
            return $this->cachedJson ?? $default;
        }

        // Support dot notation for nested keys
        $value = $this->cachedJson;
        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } elseif (is_object($value) && isset($value->$segment)) {
                $value = $value->$segment;
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Get JSON response or throw an exception if the request failed
     */
    public function jsonOrFail(?string $key = null, mixed $default = null): mixed
    {
        $this->throw();
        return $this->json($key, $default);
    }

    /**
     * Get response as object
     */
    public function object(?string $key = null, mixed $default = null): mixed
    {
        return $this->json($key, $default, false);
    }

    /**
     * Collect response as a collection-like array with helper methods
     */
    public function collect(?string $key = null): ResponseCollection
    {
        $data = $this->json($key, []);
        return new ResponseCollection(is_array($data) ? $data : []);
    }

    /**
     * Throw an exception if the response indicates a failure
     */
    public function throw(?callable $callback = null): self
    {
        if ($this->failed()) {
            $status = $this->status();
            $body = $this->body();

            // Try to extract error message
            $message = "HTTP request returned status code {$status}";
            $json = $this->json();

            if (is_array($json)) {
                $message = $json['message'] ?? $json['error'] ?? $json['error_description'] ?? $message;
            }

            $exception = new RequestException($message, $status, $body, $this);

            if ($callback) {
                $callback($exception, $this);
            }

            throw $exception;
        }

        return $this;
    }

    /**
     * Throw an exception if a condition is true
     */
    public function throwIf(bool|callable $condition, ?string $message = null): self
    {
        $shouldThrow = is_callable($condition) ? $condition($this) : $condition;

        if ($shouldThrow) {
            $message = $message ?? "HTTP request condition failed";
            throw new RequestException($message, $this->status());
        }

        return $this;
    }

    /**
     * Throw an exception unless a condition is true
     */
    public function throwUnless(bool|callable $condition, ?string $message = null): self
    {
        $shouldNotThrow = is_callable($condition) ? $condition($this) : $condition;
        return $this->throwIf(!$shouldNotThrow, $message);
    }

    /**
     * Execute callback if response is successful
     */
    public function onSuccess(callable $callback): self
    {
        if ($this->successful()) {
            $callback($this);
        }
        return $this;
    }

    /**
     * Execute callback if response failed
     */
    public function onError(callable $callback): self
    {
        if ($this->failed()) {
            $callback($this);
        }
        return $this;
    }

    /**
     * Get response info (timing, headers size, etc.)
     */
    public function info(?string $key = null): mixed
    {
        $info = $this->response->getInfo();
        return $key ? ($info[$key] ?? null) : $info;
    }

    /**
     * Get the effective URL (after redirects)
     */
    public function effectiveUrl(): ?string
    {
        return $this->info('url');
    }

    /**
     * Get the total time taken for the request
     */
    public function totalTime(): ?float
    {
        return $this->info('total_time');
    }

    /**
     * Get the underlying Symfony response
     */
    public function toSymfonyResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Convert response to array
     */
    public function toArray(): array
    {
        return $this->json() ?? [];
    }

    /**
     * Get response body or default value
     */
    public function bodyOr(string $default): string
    {
        $body = $this->body();
        return empty($body) ? $default : $body;
    }

    /**
     * Check if response body is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->body());
    }

    /**
     * Get response cookies
     */
    public function cookies(): array
    {
        $cookies = [];
        $setCookieHeaders = $this->headerValues('Set-Cookie');

        foreach ($setCookieHeaders as $header) {
            if (preg_match('/^([^=]+)=([^;]+)/', $header, $matches)) {
                $cookies[$matches[1]] = $matches[2];
            }
        }

        return $cookies;
    }

    /**
     * Get a specific cookie value
     */
    public function cookie(string $name): ?string
    {
        return $this->cookies()[$name] ?? null;
    }

    /**
     * Dump the response and continue
     */
    public function dd(): never
    {
        dd([
            'status' => $this->status(),
            'headers' => $this->headers(),
            'body' => $this->body(),
        ]);
    }

    /**
     * Dump the response and continue
     */
    public function dump(): self
    {
        dump([
            'status' => $this->status(),
            'headers' => $this->headers(),
            'body' => $this->body(),
        ]);
        return $this;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->body();
    }
}