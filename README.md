# ElliePHP HttpClient

A simple, Laravel-inspired HTTP client abstraction built on top of Symfony HttpClient. This library provides a fluent, developer-friendly interface for making HTTP requests in PHP applications.

## Features

- 🚀 **Simple & Intuitive API** - Fluent interface for building requests
- 🔄 **Static & Instance Methods** - Use whichever style fits your needs
- 🔐 **Built-in Authentication** - Bearer tokens and Basic auth support
- 📦 **JSON Handling** - Automatic encoding/decoding with convenience methods
- ⚡ **Retry Logic** - Configurable retry strategies with exponential backoff
- ⏱️ **Timeout Control** - Set request timeouts easily
- 🛡️ **Error Handling** - Graceful error handling with custom exceptions
- 🎯 **Response Helpers** - Convenient methods for checking status and accessing data

## Installation

Install via Composer:

```bash
composer require elliephp/httpclient
```

## Requirements

- PHP 8.4 or higher
- Symfony HttpClient component

## Quick Start

### Http Facade (Recommended)

The `Http` facade provides a clean, static interface for making requests:

```php
use ElliePHP\Components\HttpClient\Http;

// Simple GET request
$response = Http::get('https://api.example.com/users');

// Configured request with method chaining
$response = Http::withBaseUrl('https://api.example.com')
    ->withToken('your-api-token')
    ->withUserAgent('MyApp/1.0')
    ->acceptJson()
    ->get('/users');

// POST request
$response = Http::post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Check response
if ($response->successful()) {
    $data = $response->json();
    echo "User created: " . $data['name'];
}
```

### Static Methods (HttpClient)

For quick, one-off requests, use static methods on `HttpClient`:

```php
use ElliePHP\Components\HttpClient\HttpClient;

// GET request
$response = HttpClient::get('https://api.example.com/users');

// POST request
$response = HttpClient::post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Check response
if ($response->successful()) {
    $data = $response->json();
    echo "User created: " . $data['name'];
}
```

### Instance Methods (Configured Usage)

For multiple requests with shared configuration, create an instance:

```php
$client = new HttpClient();

$response = $client
    ->withBaseUrl('https://api.example.com')
    ->withToken('your-api-token')
    ->acceptJson()
    ->get('/users');
```

## Usage Examples

### Making Requests

#### GET Request

```php
use ElliePHP\Components\HttpClient\Http;

// Simple GET
$response = Http::get('https://api.example.com/users');

// GET with query parameters
$response = Http::get('https://api.example.com/users', [
    'page' => 1,
    'limit' => 10
]);
```

#### POST Request

```php
use ElliePHP\Components\HttpClient\Http;

// POST with form data
$response = Http::post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// POST with JSON
$response = Http::asJson()
    ->post('https://api.example.com/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
```

#### PUT Request

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::put('https://api.example.com/users/123', [
    'name' => 'Jane Doe'
]);
```

#### PATCH Request

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::patch('https://api.example.com/users/123', [
    'status' => 'active'
]);
```

#### DELETE Request

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::delete('https://api.example.com/users/123');
```

#### File Uploads

The HTTP client supports file uploads using the `attach()` method. Files are automatically sent as `multipart/form-data`.

**Upload a single file:**

```php
use ElliePHP\Components\HttpClient\Http;

// Upload a file by path
$response = Http::attach('file', '/path/to/image.jpg')
    ->post('https://api.example.com/upload');

// Upload with additional form data
$response = Http::attach('file', '/path/to/image.jpg')
    ->post('https://api.example.com/upload', [
        'description' => 'My uploaded image',
        'category' => 'photos'
    ]);
```

**Upload multiple files:**

```php
// Attach multiple files
$response = Http::attach('avatar', '/path/to/avatar.jpg')
    ->attach('document', '/path/to/document.pdf')
    ->post('https://api.example.com/upload', [
        'user_id' => 123
    ]);
```

**Upload using file resource:**

```php
// Open file and upload
$file = fopen('/path/to/file.jpg', 'r');
$response = Http::attach('file', $file)
    ->post('https://api.example.com/upload');

// Don't forget to close the file if you opened it manually
fclose($file);
```

**Upload with file resource in data array:**

```php
// You can also pass file resources directly in the data array
$response = Http::post('https://api.example.com/upload', [
    'name' => 'John',
    'file' => fopen('/path/to/file.jpg', 'r')
]);
```

**Upload with authentication and configuration:**

```php
$response = Http::withBaseUrl('https://api.example.com')
    ->withToken('your-api-token')
    ->withUserAgent('MyApp/1.0')
    ->attach('file', '/path/to/file.jpg')
    ->post('/upload', [
        'description' => 'Uploaded file'
    ]);
```

**Error Handling:**

The `attach()` method will throw an `InvalidArgumentException` if:
- The file path doesn't exist
- The file is not readable
- The provided value is not a file path or resource

```php
use ElliePHP\Components\HttpClient\Http;
use InvalidArgumentException;

try {
    $response = Http::attach('file', '/nonexistent/file.jpg')
        ->post('https://api.example.com/upload');
} catch (InvalidArgumentException $e) {
    echo "File error: " . $e->getMessage();
}
```

**Note:** File uploads work with POST, PUT, and PATCH requests. When files are attached, the request is automatically sent as `multipart/form-data`, even if `asJson()` was called earlier.

### Authentication

#### Bearer Token Authentication

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::withToken('your-api-token')
    ->get('https://api.example.com/protected-resource');
```

#### Basic Authentication

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::withBasicAuth('username', 'password')
    ->get('https://api.example.com/protected-resource');
```

### Working with JSON

#### Sending JSON Requests

```php
use ElliePHP\Components\HttpClient\Http;

// asJson() sets Content-Type header and encodes body as JSON
$response = Http::asJson()
    ->post('https://api.example.com/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'metadata' => [
            'role' => 'admin',
            'department' => 'IT'
        ]
    ]);
```

#### Receiving JSON Responses

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::get('https://api.example.com/users/123');

// Get entire JSON response as array
$data = $response->json();
echo $data['name']; // John Doe

// Get specific key from JSON
$name = $response->json('name');
echo $name; // John Doe

// Handle invalid JSON gracefully
$data = $response->json(); // Returns null if JSON is invalid
```

#### Accept JSON Header

```php
use ElliePHP\Components\HttpClient\Http;

// Sets Accept: application/json header
$response = Http::acceptJson()
    ->get('https://api.example.com/users');
```

### Configuration Options

#### Base URL

```php
use ElliePHP\Components\HttpClient\Http;

// Set base URL for all requests
$response = Http::withBaseUrl('https://api.example.com')
    ->get('/users'); // Requests https://api.example.com/users

// Absolute URLs override base URL
$response = Http::withBaseUrl('https://api.example.com')
    ->get('https://other-api.com/data'); // Requests https://other-api.com/data
```

#### Custom Headers

```php
$client = new HttpClient();

// Add multiple headers
$response = $client
    ->withHeaders([
        'X-API-Key' => 'secret-key',
        'User-Agent' => 'MyApp/1.0',
        'X-Custom-Header' => 'value'
    ])
    ->get('https://api.example.com/data');

// Or use convenience methods for common headers
$response = Http::withUserAgent('MyApp/1.0')
    ->withHeader('X-API-Key', 'secret-key')
    ->get('https://api.example.com/data');
```

#### User-Agent Header

```php
// Set User-Agent header
$response = Http::withUserAgent('MyApp/1.0')
    ->get('https://api.example.com/data');
```

#### Content-Type Header

```php
// Set Content-Type header
$response = Http::withContentType('application/xml')
    ->post('https://api.example.com/data', $xmlData);
```

#### Accept Header

```php
// Set Accept header
$response = Http::withAccept('application/xml')
    ->get('https://api.example.com/data');
```

#### Single Header

```php
// Set a single custom header
$response = Http::withHeader('X-Custom-Header', 'value')
    ->get('https://api.example.com/data');
```

#### Maximum Redirects

```php
// Configure maximum number of redirects to follow
$response = Http::withMaxRedirects(5)
    ->get('https://api.example.com/data');
```

#### SSL Verification

```php
// Disable SSL certificate verification (not recommended for production)
$response = Http::withVerify(false)
    ->get('https://self-signed-cert.example.com');

// Enable SSL verification (default)
$response = Http::withVerify(true)
    ->get('https://api.example.com/data');
```

#### Proxy Configuration

```php
// Configure proxy for requests
$response = Http::withProxy('http://proxy.example.com:8080')
    ->get('https://api.example.com/data');
```

#### Timeout

```php
use ElliePHP\Components\HttpClient\Http;

// Set timeout in seconds
$response = Http::withTimeout(30)
    ->get('https://api.example.com/slow-endpoint');
```

### Retry Configuration

Configure automatic retry behavior for failed requests:

#### Exponential Backoff

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::withRetry([
    'max_retries' => 3,      // Retry up to 3 times
    'delay' => 1000,         // Start with 1 second delay (milliseconds)
    'multiplier' => 2,       // Double delay each time: 1s, 2s, 4s
    'max_delay' => 10000,    // Cap delay at 10 seconds
])
    ->get('https://api.example.com/data');
```

#### Fixed Delay

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::withRetry([
    'max_retries' => 5,
    'delay' => 2000,         // 2 second delay
    'multiplier' => 1,       // Keep delay constant
])
    ->get('https://api.example.com/data');
```

#### Retry with Jitter

Add randomness to prevent thundering herd:

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::withRetry([
    'max_retries' => 3,
    'delay' => 1000,
    'multiplier' => 2,
    'jitter' => 0.1,         // Add ±10% random variation
])
    ->get('https://api.example.com/data');
```

#### Retry Specific Status Codes

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::withRetry([
    'max_retries' => 3,
    'delay' => 1000,
    'multiplier' => 2,
    'http_codes' => [429, 500, 502, 503, 504], // Only retry these codes
])
    ->get('https://api.example.com/data');
```

### Advanced Configuration

#### Symfony HttpClient Options

Pass any Symfony HttpClient options directly:

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::withOptions([
    'max_redirects' => 5,
    'timeout' => 30,
    'verify_peer' => true,
    'verify_host' => true,
])
    ->get('https://api.example.com/data');
```

For all available options, see the [Symfony HttpClient documentation](https://symfony.com/doc/current/http_client.html#configuration).

### Response Handling

#### Check Response Status

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::get('https://api.example.com/users');

// Check if successful (2xx status)
if ($response->successful()) {
    echo "Request succeeded!";
}

// Check if failed (4xx or 5xx status)
if ($response->failed()) {
    echo "Request failed!";
}

// Get status code
$status = $response->status(); // e.g., 200, 404, 500
```

#### Access Response Data

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::get('https://api.example.com/users');

// Get raw body
$body = $response->body();

// Get JSON data
$data = $response->json();

// Get specific JSON key
$name = $response->json('name');
```

#### Access Response Headers

```php
use ElliePHP\Components\HttpClient\Http;

$response = Http::get('https://api.example.com/users');

// Get all headers
$headers = $response->headers();

// Get specific header
$contentType = $response->header('Content-Type');
$rateLimit = $response->header('X-RateLimit-Remaining');
```

### Error Handling

The library throws `RequestException` for network errors and timeouts:

```php
use ElliePHP\Components\HttpClient\Http;
use ElliePHP\Components\HttpClient\RequestException;

try {
    $response = Http::get('https://api.example.com/users');
    
    if ($response->successful()) {
        $data = $response->json();
        // Process data
    } else {
        // Handle 4xx/5xx responses
        echo "HTTP Error: " . $response->status();
    }
} catch (RequestException $e) {
    // Handle network errors, timeouts, etc.
    echo "Request failed: " . $e->getMessage();
    
    // Access original exception if needed
    $previous = $e->getPrevious();
}
```

#### Exception Types

- **Network Errors**: Connection failures, DNS resolution errors, SSL errors
- **Timeout Errors**: Request exceeds configured timeout
- **Transport Errors**: Other Symfony transport-level errors

**Note**: 4xx and 5xx HTTP responses do NOT throw exceptions by default. Use `$response->successful()` or `$response->failed()` to check status.

## Method Chaining

All configuration methods return a `ClientBuilder` instance, allowing fluent method chaining:

```php
// Using Http facade
$response = Http::withBaseUrl('https://api.example.com')
    ->withToken('your-api-token')
    ->withUserAgent('MyApp/1.0')
    ->withTimeout(30)
    ->withMaxRedirects(5)
    ->withRetry([
        'max_retries' => 3,
        'delay' => 1000,
        'multiplier' => 2,
    ])
    ->acceptJson()
    ->asJson()
    ->post('/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);

// Or using HttpClient instance
$client = new HttpClient();
$response = $client
    ->withBaseUrl('https://api.example.com')
    ->withToken('your-api-token')
    ->withTimeout(30)
    ->withRetry([
        'max_retries' => 3,
        'delay' => 1000,
        'multiplier' => 2,
    ])
    ->acceptJson()
    ->asJson()
    ->post('/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
```

## Complete Examples

### Example 1: Simple API Client

```php
use ElliePHP\Components\HttpClient\Http;

// Quick one-off requests using Http facade
$users = Http::get('https://api.example.com/users')->json();

foreach ($users as $user) {
    echo $user['name'] . "\n";
}
```

### Example 2: Configured API Client

```php
use ElliePHP\Components\HttpClient\HttpClient;
use ElliePHP\Components\HttpClient\RequestException;

class ApiClient
{
    private HttpClient $client;
    
    public function __construct(string $apiToken)
    {
        $this->client = new HttpClient();
    }
    
    public function getUsers(int $page = 1): array
    {
        try {
            $response = $this->client
                ->withBaseUrl('https://api.example.com')
                ->withToken($apiToken)
                ->withUserAgent('MyApp/1.0')
                ->withTimeout(30)
                ->acceptJson()
                ->get('/users', ['page' => $page]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new \Exception('Failed to fetch users: ' . $response->status());
        } catch (RequestException $e) {
            throw new \Exception('API request failed: ' . $e->getMessage(), 0, $e);
        }
    }
    
    public function createUser(array $userData): array
    {
        try {
            $response = $this->client
                ->withBaseUrl('https://api.example.com')
                ->withToken($apiToken)
                ->asJson()
                ->post('/users', $userData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new \Exception('Failed to create user: ' . $response->status());
        } catch (RequestException $e) {
            throw new \Exception('API request failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
```

### Example 3: Resilient API Client with Retries

```php
use ElliePHP\Components\HttpClient\Http;

// Configure for resilient API calls using Http facade
$response = Http::withBaseUrl('https://api.example.com')
    ->withToken('your-api-token')
    ->withUserAgent('MyApp/1.0')
    ->withTimeout(30)
    ->withMaxRedirects(5)
    ->withRetry([
        'max_retries' => 3,
        'delay' => 1000,
        'multiplier' => 2,
        'jitter' => 0.1,
        'http_codes' => [429, 500, 502, 503, 504],
    ])
    ->acceptJson()
    ->asJson()
    ->post('/orders', [
        'product_id' => 123,
        'quantity' => 2,
        'customer_id' => 456
    ]);

if ($response->successful()) {
    $order = $response->json();
    echo "Order created: " . $order['id'];
} else {
    echo "Order failed: " . $response->status();
}
```

### Example 4: Using Convenience Methods

```php
use ElliePHP\Components\HttpClient\Http;

// Chain convenience methods for clean, readable code
$response = Http::withBaseUrl('https://api.example.com')
    ->withUserAgent('MyApp/2.0')
    ->withContentType('application/json')
    ->withAccept('application/json')
    ->withHeader('X-API-Version', 'v2')
    ->withTimeout(30)
    ->withMaxRedirects(3)
    ->withToken('your-api-token')
    ->get('/users');

if ($response->successful()) {
    $users = $response->json();
    // Process users...
}
```

### Example 5: File Upload

```php
use ElliePHP\Components\HttpClient\Http;
use ElliePHP\Components\HttpClient\RequestException;
use InvalidArgumentException;

try {
    // Upload a single file with metadata
    $response = Http::withBaseUrl('https://api.example.com')
        ->withToken('your-api-token')
        ->withUserAgent('MyApp/1.0')
        ->attach('file', '/path/to/document.pdf')
        ->post('/upload', [
            'title' => 'Important Document',
            'category' => 'legal'
        ]);
    
    if ($response->successful()) {
        $result = $response->json();
        echo "File uploaded: " . $result['file_id'];
    }
    
    // Upload multiple files
    $response = Http::withBaseUrl('https://api.example.com')
        ->withToken('your-api-token')
        ->attach('avatar', '/path/to/avatar.jpg')
        ->attach('cover', '/path/to/cover.jpg')
        ->post('/upload', [
            'user_id' => 123
        ]);
    
} catch (InvalidArgumentException $e) {
    echo "File error: " . $e->getMessage();
} catch (RequestException $e) {
    echo "Upload failed: " . $e->getMessage();
}
```

## API Reference

### Http Facade

The `Http` facade provides a static interface that delegates to `HttpClient`. All methods available on `HttpClient` are also available on `Http`.

```php
use ElliePHP\Components\HttpClient\Http;

// All HttpClient methods work with Http facade
Http::get('https://api.example.com/users');
Http::withBaseUrl('https://api.example.com')->get('/users');
Http::withToken('token')->get('/api/protected');
```

### HttpClient

#### Static Methods

- `HttpClient::get(string $url, array $query = []): Response`
- `HttpClient::post(string $url, array $data = []): Response`
- `HttpClient::put(string $url, array $data = []): Response`
- `HttpClient::patch(string $url, array $data = []): Response`
- `HttpClient::delete(string $url): Response`

#### Configuration Methods

- `withBaseUrl(string $baseUrl): ClientBuilder` - Set base URL for requests
- `withHeaders(array $headers): ClientBuilder` - Add multiple headers
- `withHeader(string $name, string $value): ClientBuilder` - Set a single header
- `withUserAgent(string $userAgent): ClientBuilder` - Set User-Agent header
- `withContentType(string $contentType): ClientBuilder` - Set Content-Type header
- `withAccept(string $accept): ClientBuilder` - Set Accept header
- `withToken(string $token): ClientBuilder` - Set Bearer token authentication
- `withBasicAuth(string $username, string $password): ClientBuilder` - Set Basic authentication
- `acceptJson(): ClientBuilder` - Set Accept: application/json header
- `asJson(): ClientBuilder` - Set Content-Type: application/json and enable JSON encoding
- `withTimeout(int $seconds): ClientBuilder` - Set request timeout
- `withMaxRedirects(int $maxRedirects): ClientBuilder` - Set maximum redirects to follow
- `withVerify(bool $verify = true): ClientBuilder` - Enable/disable SSL verification
- `withProxy(string $proxyUrl): ClientBuilder` - Configure proxy settings
- `withRetry(array $retryConfig): ClientBuilder` - Configure retry behavior
- `withOptions(array $options): ClientBuilder` - Set Symfony HttpClient options
- `attach(string $name, string|resource $file): ClientBuilder` - Attach a file to upload with the request

#### Request Methods

- `get(string $url, array $query = []): Response`
- `post(string $url, array $data = []): Response`
- `put(string $url, array $data = []): Response`
- `patch(string $url, array $data = []): Response`
- `delete(string $url): Response`

### Response

#### Status Methods

- `status(): int` - Get HTTP status code
- `successful(): bool` - Check if status is 2xx
- `failed(): bool` - Check if status is 4xx or 5xx

#### Content Methods

- `body(): string` - Get raw response body
- `json(?string $key = null): mixed` - Decode JSON response
- `headers(): array` - Get all response headers
- `header(string $name): ?string` - Get specific header

### RequestException

Custom exception thrown for network errors, timeouts, and transport failures.

```php
use ElliePHP\Components\HttpClient\Http;
use ElliePHP\Components\HttpClient\RequestException;

try {
    $response = Http::get('https://api.example.com/data');
} catch (RequestException $e) {
    echo $e->getMessage();      // Error message
    echo $e->getCode();         // Error code
    $previous = $e->getPrevious(); // Original exception
}
```

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test:coverage
```

## License

This library is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

- **Issues**: [GitHub Issues](https://github.com/elliephp/httpclient/issues)
- **Source**: [GitHub Repository](https://github.com/elliephp/httpclient)

## Credits

Built on top of [Symfony HttpClient](https://symfony.com/doc/current/http_client.html).
