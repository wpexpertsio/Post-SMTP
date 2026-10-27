<?php

declare (strict_types=1);
namespace PostSMTP\Vendor\GuzzleHttp\Psr7;

use PostSMTP\Vendor\Psr\Http\Message\RequestFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\RequestInterface;
use PostSMTP\Vendor\Psr\Http\Message\ResponseFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\ResponseInterface;
use PostSMTP\Vendor\Psr\Http\Message\ServerRequestFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\ServerRequestInterface;
use PostSMTP\Vendor\Psr\Http\Message\StreamFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\StreamInterface;
use PostSMTP\Vendor\Psr\Http\Message\UploadedFileFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\UploadedFileInterface;
use PostSMTP\Vendor\Psr\Http\Message\UriFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\UriInterface;
/**
 * Implements all of the PSR-17 interfaces.
 *
 * Note: in consuming code it is recommended to require the implemented interfaces
 * and inject the instance of this class multiple times.
 */
final class HttpFactory implements \PostSMTP\Vendor\Psr\Http\Message\RequestFactoryInterface, \PostSMTP\Vendor\Psr\Http\Message\ResponseFactoryInterface, \PostSMTP\Vendor\Psr\Http\Message\ServerRequestFactoryInterface, \PostSMTP\Vendor\Psr\Http\Message\StreamFactoryInterface, \PostSMTP\Vendor\Psr\Http\Message\UploadedFileFactoryInterface, \PostSMTP\Vendor\Psr\Http\Message\UriFactoryInterface
{
    public function createUploadedFile(\PostSMTP\Vendor\Psr\Http\Message\StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null) : \PostSMTP\Vendor\Psr\Http\Message\UploadedFileInterface
    {
        if ($size === null) {
            $size = $stream->getSize();
        }
        return new \PostSMTP\Vendor\GuzzleHttp\Psr7\UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }
    public function createStream(string $content = '') : \PostSMTP\Vendor\Psr\Http\Message\StreamInterface
    {
        return \PostSMTP\Vendor\GuzzleHttp\Psr7\Utils::streamFor($content);
    }
    public function createStreamFromFile(string $file, string $mode = 'r') : \PostSMTP\Vendor\Psr\Http\Message\StreamInterface
    {
        try {
            $resource = \PostSMTP\Vendor\GuzzleHttp\Psr7\Utils::tryFopen($file, $mode);
        } catch (\RuntimeException $e) {
            if ('' === $mode || \false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], \true)) {
                throw new \InvalidArgumentException(\sprintf('Invalid file opening mode "%s"', $mode), 0, $e);
            }
            throw $e;
        }
        return \PostSMTP\Vendor\GuzzleHttp\Psr7\Utils::streamFor($resource);
    }
    public function createStreamFromResource($resource) : \PostSMTP\Vendor\Psr\Http\Message\StreamInterface
    {
        return \PostSMTP\Vendor\GuzzleHttp\Psr7\Utils::streamFor($resource);
    }
    public function createServerRequest(string $method, $uri, array $serverParams = []) : \PostSMTP\Vendor\Psr\Http\Message\ServerRequestInterface
    {
        if (empty($method)) {
            if (!empty($serverParams['REQUEST_METHOD'])) {
                $method = $serverParams['REQUEST_METHOD'];
            } else {
                throw new \InvalidArgumentException('Cannot determine HTTP method');
            }
        }
        return new \PostSMTP\Vendor\GuzzleHttp\Psr7\ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }
    public function createResponse(int $code = 200, string $reasonPhrase = '') : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface
    {
        return new \PostSMTP\Vendor\GuzzleHttp\Psr7\Response($code, [], null, '1.1', $reasonPhrase);
    }
    public function createRequest(string $method, $uri) : \PostSMTP\Vendor\Psr\Http\Message\RequestInterface
    {
        return new \PostSMTP\Vendor\GuzzleHttp\Psr7\Request($method, $uri);
    }
    public function createUri(string $uri = '') : \PostSMTP\Vendor\Psr\Http\Message\UriInterface
    {
        return new \PostSMTP\Vendor\GuzzleHttp\Psr7\Uri($uri);
    }
}
