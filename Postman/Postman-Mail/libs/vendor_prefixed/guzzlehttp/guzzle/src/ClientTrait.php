<?php

namespace PostSMTP\Vendor\GuzzleHttp;

use PostSMTP\Vendor\GuzzleHttp\Exception\GuzzleException;
use PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface;
use PostSMTP\Vendor\Psr\Http\Message\ResponseInterface;
use PostSMTP\Vendor\Psr\Http\Message\UriInterface;
/**
 * Client interface for sending HTTP requests.
 */
trait ClientTrait
{
    /**
     * Create and send an HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string              $method  HTTP method.
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @throws GuzzleException
     */
    public abstract function request(string $method, $uri, array $options = []) : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface;
    /**
     * Create and send an HTTP GET request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @throws GuzzleException
     */
    public function get($uri, array $options = []) : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }
    /**
     * Create and send an HTTP HEAD request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @throws GuzzleException
     */
    public function head($uri, array $options = []) : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface
    {
        return $this->request('HEAD', $uri, $options);
    }
    /**
     * Create and send an HTTP PUT request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @throws GuzzleException
     */
    public function put($uri, array $options = []) : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface
    {
        return $this->request('PUT', $uri, $options);
    }
    /**
     * Create and send an HTTP POST request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @throws GuzzleException
     */
    public function post($uri, array $options = []) : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }
    /**
     * Create and send an HTTP PATCH request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @throws GuzzleException
     */
    public function patch($uri, array $options = []) : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface
    {
        return $this->request('PATCH', $uri, $options);
    }
    /**
     * Create and send an HTTP DELETE request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     *
     * @throws GuzzleException
     */
    public function delete($uri, array $options = []) : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }
    /**
     * Create and send an asynchronous HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string              $method  HTTP method
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     */
    public abstract function requestAsync(string $method, $uri, array $options = []) : \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface;
    /**
     * Create and send an asynchronous HTTP GET request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     */
    public function getAsync($uri, array $options = []) : \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface
    {
        return $this->requestAsync('GET', $uri, $options);
    }
    /**
     * Create and send an asynchronous HTTP HEAD request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     */
    public function headAsync($uri, array $options = []) : \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface
    {
        return $this->requestAsync('HEAD', $uri, $options);
    }
    /**
     * Create and send an asynchronous HTTP PUT request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     */
    public function putAsync($uri, array $options = []) : \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface
    {
        return $this->requestAsync('PUT', $uri, $options);
    }
    /**
     * Create and send an asynchronous HTTP POST request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     */
    public function postAsync($uri, array $options = []) : \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface
    {
        return $this->requestAsync('POST', $uri, $options);
    }
    /**
     * Create and send an asynchronous HTTP PATCH request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     */
    public function patchAsync($uri, array $options = []) : \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface
    {
        return $this->requestAsync('PATCH', $uri, $options);
    }
    /**
     * Create and send an asynchronous HTTP DELETE request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string|UriInterface $uri     URI object or string.
     * @param array               $options Request options to apply.
     */
    public function deleteAsync($uri, array $options = []) : \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface
    {
        return $this->requestAsync('DELETE', $uri, $options);
    }
}
