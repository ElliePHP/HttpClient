<?php

namespace ElliePHP\Components\HttpClient;

/**
 * Http - Static facade for HttpClient
 * 
 * Provides a convenient static interface to the HttpClient class.
 * All static method calls are forwarded to HttpClient instances.
 * 
 * Example usage:
 * ```php
 * use ElliePHP\Components\HttpClient\Http;
 * 
 * $response = Http::get('https://api.example.com/users');
 * $response = Http::withBaseUrl('https://api.example.com')->get('/users');
 * $response = Http::withToken('token')->get('/api/protected');
 * ```
 */
class Http
{
    /**
     * Forward all static method calls to HttpClient
     * 
     * This allows Http::get(), Http::post(), Http::withBaseUrl(), etc.
     * to work by delegating to HttpClient's static methods.
     * 
     * @param string $method The method name
     * @param array $arguments The method arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        $client = new HttpClient();
        return $client->$method(...$arguments);
    }
}

