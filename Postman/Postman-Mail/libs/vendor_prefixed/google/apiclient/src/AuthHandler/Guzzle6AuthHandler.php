<?php

namespace PostSMTP\Vendor\Google\AuthHandler;

use PostSMTP\Vendor\Google\Auth\CredentialsLoader;
use PostSMTP\Vendor\Google\Auth\HttpHandler\HttpHandlerFactory;
use PostSMTP\Vendor\Google\Auth\FetchAuthTokenCache;
use PostSMTP\Vendor\Google\Auth\Middleware\AuthTokenMiddleware;
use PostSMTP\Vendor\Google\Auth\Middleware\ScopedAccessTokenMiddleware;
use PostSMTP\Vendor\Google\Auth\Middleware\SimpleMiddleware;
use PostSMTP\Vendor\GuzzleHttp\Client;
use PostSMTP\Vendor\GuzzleHttp\ClientInterface;
use PostSMTP\Vendor\Psr\Cache\CacheItemPoolInterface;
/**
* This supports Guzzle 6
*/
class Guzzle6AuthHandler
{
    protected $cache;
    protected $cacheConfig;
    public function __construct(\PostSMTP\Vendor\Psr\Cache\CacheItemPoolInterface $cache = null, array $cacheConfig = [])
    {
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
    }
    public function attachCredentials(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $http, \PostSMTP\Vendor\Google\Auth\CredentialsLoader $credentials, callable $tokenCallback = null)
    {
        // use the provided cache
        if ($this->cache) {
            $credentials = new \PostSMTP\Vendor\Google\Auth\FetchAuthTokenCache($credentials, $this->cacheConfig, $this->cache);
        }
        return $this->attachCredentialsCache($http, $credentials, $tokenCallback);
    }
    public function attachCredentialsCache(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $http, \PostSMTP\Vendor\Google\Auth\FetchAuthTokenCache $credentials, callable $tokenCallback = null)
    {
        // if we end up needing to make an HTTP request to retrieve credentials, we
        // can use our existing one, but we need to throw exceptions so the error
        // bubbles up.
        $authHttp = $this->createAuthHttp($http);
        $authHttpHandler = \PostSMTP\Vendor\Google\Auth\HttpHandler\HttpHandlerFactory::build($authHttp);
        $middleware = new \PostSMTP\Vendor\Google\Auth\Middleware\AuthTokenMiddleware($credentials, $authHttpHandler, $tokenCallback);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'google_auth';
        $http = new \PostSMTP\Vendor\GuzzleHttp\Client($config);
        return $http;
    }
    public function attachToken(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $http, array $token, array $scopes)
    {
        $tokenFunc = function ($scopes) use($token) {
            return $token['access_token'];
        };
        $middleware = new \PostSMTP\Vendor\Google\Auth\Middleware\ScopedAccessTokenMiddleware($tokenFunc, $scopes, $this->cacheConfig, $this->cache);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'scoped';
        $http = new \PostSMTP\Vendor\GuzzleHttp\Client($config);
        return $http;
    }
    public function attachKey(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $http, $key)
    {
        $middleware = new \PostSMTP\Vendor\Google\Auth\Middleware\SimpleMiddleware(['key' => $key]);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'simple';
        $http = new \PostSMTP\Vendor\GuzzleHttp\Client($config);
        return $http;
    }
    private function createAuthHttp(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $http)
    {
        return new \PostSMTP\Vendor\GuzzleHttp\Client(['base_uri' => $http->getConfig('base_uri'), 'http_errors' => \true, 'verify' => $http->getConfig('verify'), 'proxy' => $http->getConfig('proxy')]);
    }
}
