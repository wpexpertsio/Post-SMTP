<?php

/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace PostSMTP\Vendor\Google\Http;

use PostSMTP\Vendor\Google\Auth\HttpHandler\HttpHandlerFactory;
use PostSMTP\Vendor\Google\Client;
use PostSMTP\Vendor\Google\Task\Runner;
use PostSMTP\Vendor\Google\Service\Exception as GoogleServiceException;
use PostSMTP\Vendor\GuzzleHttp\ClientInterface;
use PostSMTP\Vendor\GuzzleHttp\Exception\RequestException;
use PostSMTP\Vendor\GuzzleHttp\Psr7\Response;
use PostSMTP\Vendor\Psr\Http\Message\RequestInterface;
use PostSMTP\Vendor\Psr\Http\Message\ResponseInterface;
/**
 * This class implements the RESTful transport of apiServiceRequest()'s
 */
class REST
{
    /**
     * Executes a Psr\Http\Message\RequestInterface and (if applicable) automatically retries
     * when errors occur.
     *
     * @param Client $client
     * @param RequestInterface $req
     * @param string $expectedClass
     * @param array $config
     * @param array $retryMap
     * @return mixed decoded result
     * @throws \Google\Service\Exception on server side error (ie: not authenticated,
     *  invalid or malformed post body, invalid url)
     */
    public static function execute(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $client, \PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request, $expectedClass = null, $config = array(), $retryMap = null)
    {
        $runner = new \PostSMTP\Vendor\Google\Task\Runner($config, \sprintf('%s %s', $request->getMethod(), (string) $request->getUri()), array(\get_class(), 'doExecute'), array($client, $request, $expectedClass));
        if (null !== $retryMap) {
            $runner->setRetryMap($retryMap);
        }
        return $runner->run();
    }
    /**
     * Executes a Psr\Http\Message\RequestInterface
     *
     * @param Client $client
     * @param RequestInterface $request
     * @param string $expectedClass
     * @return array decoded result
     * @throws \Google\Service\Exception on server side error (ie: not authenticated,
     *  invalid or malformed post body, invalid url)
     */
    public static function doExecute(\PostSMTP\Vendor\GuzzleHttp\ClientInterface $client, \PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request, $expectedClass = null)
    {
        try {
            $httpHandler = \PostSMTP\Vendor\Google\Auth\HttpHandler\HttpHandlerFactory::build($client);
            $response = $httpHandler($request);
        } catch (\PostSMTP\Vendor\GuzzleHttp\Exception\RequestException $e) {
            // if Guzzle throws an exception, catch it and handle the response
            if (!$e->hasResponse()) {
                throw $e;
            }
            $response = $e->getResponse();
            // specific checking for Guzzle 5: convert to PSR7 response
            if ($response instanceof \PostSMTP\Vendor\GuzzleHttp\Message\ResponseInterface) {
                $response = new \PostSMTP\Vendor\GuzzleHttp\Psr7\Response($response->getStatusCode(), $response->getHeaders() ?: [], $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
            }
        }
        return self::decodeHttpResponse($response, $request, $expectedClass);
    }
    /**
     * Decode an HTTP Response.
     * @static
     * @throws \Google\Service\Exception
     * @param RequestInterface $response The http response to be decoded.
     * @param ResponseInterface $response
     * @param string $expectedClass
     * @return mixed|null
     */
    public static function decodeHttpResponse(\PostSMTP\Vendor\Psr\Http\Message\ResponseInterface $response, \PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request = null, $expectedClass = null)
    {
        $code = $response->getStatusCode();
        // retry strategy
        if (\intVal($code) >= 400) {
            // if we errored out, it should be safe to grab the response body
            $body = (string) $response->getBody();
            // Check if we received errors, and add those to the Exception for convenience
            throw new \PostSMTP\Vendor\Google\Service\Exception($body, $code, null, self::getResponseErrors($body));
        }
        // Ensure we only pull the entire body into memory if the request is not
        // of media type
        $body = self::decodeBody($response, $request);
        if ($expectedClass = self::determineExpectedClass($expectedClass, $request)) {
            $json = \json_decode($body, \true);
            return new $expectedClass($json);
        }
        return $response;
    }
    private static function decodeBody(\PostSMTP\Vendor\Psr\Http\Message\ResponseInterface $response, \PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request = null)
    {
        if (self::isAltMedia($request)) {
            // don't decode the body, it's probably a really long string
            return '';
        }
        return (string) $response->getBody();
    }
    private static function determineExpectedClass($expectedClass, \PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request = null)
    {
        // "false" is used to explicitly prevent an expected class from being returned
        if (\false === $expectedClass) {
            return null;
        }
        // if we don't have a request, we just use what's passed in
        if (null === $request) {
            return $expectedClass;
        }
        // return what we have in the request header if one was not supplied
        return $expectedClass ?: $request->getHeaderLine('X-Php-Expected-Class');
    }
    private static function getResponseErrors($body)
    {
        $json = \json_decode($body, \true);
        if (isset($json['error']['errors'])) {
            return $json['error']['errors'];
        }
        return null;
    }
    private static function isAltMedia(\PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request = null)
    {
        if ($request && ($qs = $request->getUri()->getQuery())) {
            \parse_str($qs, $query);
            if (isset($query['alt']) && $query['alt'] == 'media') {
                return \true;
            }
        }
        return \false;
    }
}
