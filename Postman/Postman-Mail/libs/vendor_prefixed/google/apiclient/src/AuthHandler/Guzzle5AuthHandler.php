<?php

namespace PostSMTP\Vendor\Google\AuthHandler;

use PostSMTP\Vendor\Google\Auth\CredentialsLoader;
use PostSMTP\Vendor\Google\Auth\FetchAuthTokenCache;
use PostSMTP\Vendor\Google\Auth\HttpHandler\HttpHandlerFactory;
use PostSMTP\Vendor\Google\Auth\Subscriber\AuthTokenSubscriber;
use PostSMTP\Vendor\Google\Auth\Subscriber\ScopedAccessTokenSubscriber;
use PostSMTP\Vendor\Google\Auth\Subscriber\SimpleSubscriber;
use PostSMTP\Vendor\GuzzleHttp\Client;
use PostSMTP\Vendor\GuzzleHttp\ClientInterface;
use PostSMTP\Vendor\Psr\Cache\CacheItemPoolInterface;
/**
 * This supports Guzzle 5
 */
class Guzzle5AuthHandler
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
        $subscriber = new \PostSMTP\Vendor\Google\Auth\Subscriber\AuthTokenSubscriber($credentials, $authHttpHandler, $tokenCallback);
        $http->setDefaultOption('auth', 'google_auth');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    public function attachToken(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $http, array $token, array $scopes)
    {
        $tokenFunc = function ($scopes) use($token) {
            return $token['access_token'];
        };
        $subscriber = new \PostSMTP\Vendor\Google\Auth\Subscriber\ScopedAccessTokenSubscriber($tokenFunc, $scopes, $this->cacheConfig, $this->cache);
        $http->setDefaultOption('auth', 'scoped');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    public function attachKey(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $http, $key)
    {
        $subscriber = new \PostSMTP\Vendor\Google\Auth\Subscriber\SimpleSubscriber(['key' => $key]);
        $http->setDefaultOption('auth', 'simple');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    private function createAuthHttp(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $http)
    {
        return new \PostSMTP\Vendor\GuzzleHttp\Client(['base_url' => $http->getBaseUrl(), 'defaults' => ['exceptions' => \true, 'verify' => $http->getDefaultOption('verify'), 'proxy' => $http->getDefaultOption('proxy')]]);
    }
}
