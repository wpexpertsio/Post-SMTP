<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels;

use BackedEnum;
use DateTimeInterface;
use PostSMTP\Vendor\Http\Discovery\Psr17FactoryDiscovery;
use PostSMTP\Vendor\Http\Discovery\Psr18ClientDiscovery;
use PostSMTP\Vendor\MailChannels\Exception\ApiException;
use PostSMTP\Vendor\MailChannels\Exception\AuthenticationException;
use PostSMTP\Vendor\MailChannels\Exception\BadGatewayException;
use PostSMTP\Vendor\MailChannels\Exception\ConfigurationException;
use PostSMTP\Vendor\MailChannels\Exception\ConflictException;
use PostSMTP\Vendor\MailChannels\Exception\ForbiddenException;
use PostSMTP\Vendor\MailChannels\Exception\InvalidRequestException;
use PostSMTP\Vendor\MailChannels\Exception\NotFoundException;
use PostSMTP\Vendor\MailChannels\Exception\PayloadTooLargeException;
use PostSMTP\Vendor\MailChannels\Exception\RateLimitException;
use PostSMTP\Vendor\MailChannels\Exception\ServerException;
use PostSMTP\Vendor\MailChannels\Exception\TransportException;
use PostSMTP\Vendor\Psr\Http\Client\ClientExceptionInterface;
use PostSMTP\Vendor\Psr\Http\Client\ClientInterface;
use PostSMTP\Vendor\Psr\Http\Message\RequestFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\ResponseInterface;
use PostSMTP\Vendor\Psr\Http\Message\StreamFactoryInterface;
use Stringable;
use UnitEnum;

/**
 * Internal HTTP transport. Builds PSR-7 requests, sends them through a
 * PSR-18 client, and decodes responses into MailChannels\Response — or
 * raises a typed exception from the MailChannels\Exception namespace.
 */
final class Transport
{
    /**
     * Well-known PSR-18 client classes that ship in widely-used packages.
     * Used to detect the multi-client ambiguity described in the README:
     * if more than one is present at runtime and the caller didn't inject a
     * client explicitly, we emit a single warning so the routing surprise
     * surfaces in logs.
     *
     * @var list<class-string>
     */
    private const KNOWN_PSR18_CLIENTS = [
        \PostSMTP\Vendor\GuzzleHttp\Client::class,
        \PostSMTP\Vendor\Symfony\Component\HttpClient\Psr18Client::class,
    ];

    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(private readonly Config $config)
    {
        if ($config->httpClient === null) {
            self::warnIfMultiplePsr18ClientsAvailable($config);
            $this->httpClient = Psr18ClientDiscovery::find();
        } else {
            $this->httpClient = $config->httpClient;
        }
        $this->requestFactory = $config->requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $config->streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Emit a one-time warning when discovery would have multiple PSR-18
     * implementations to choose from. The discovery library will pick a
     * deterministic winner, but callers who don't know which one is
     * preferred can be surprised by routing, timeouts, or middleware
     * behaviour.
     */
    private static function warnIfMultiplePsr18ClientsAvailable(Config $config): void
    {
        $available = [];
        foreach (self::KNOWN_PSR18_CLIENTS as $class) {
            if (class_exists($class)) {
                $available[] = $class;
            }
        }
        if (count($available) < 2) {
            return;
        }
        $config->logger->warning(
            'Multiple PSR-18 HTTP clients are installed; MailChannels SDK auto-discovery '
            . 'will pick one deterministically. Pass `httpClient` to MailChannels\\Client '
            . 'explicitly to remove the ambiguity.',
            ['available' => $available],
        );
    }

    /**
     * @param array<string, mixed>|null $jsonBody
     * @param array<string, mixed>|null $query
     * @param array<string, string>     $extraHeaders
     */
    public function request(
        string $method,
        string $path,
        ?array $jsonBody = null,
        ?array $query = null,
        array $extraHeaders = [],
        bool $requireApiKey = true,
    ): Response {
        $uri = $this->buildUri($path, $query);
        $request = $this->requestFactory->createRequest($method, $uri);

        foreach ($this->defaultHeaders($requireApiKey, $extraHeaders) as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($jsonBody !== null) {
            $encoded = json_encode($jsonBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new ConfigurationException(
                    'Unable to encode request payload as JSON: ' . json_last_error_msg(),
                    errorCode: 'JsonEncodeError',
                );
            }
            $request = $request->withBody($this->streamFactory->createStream($encoded));
            if (!$request->hasHeader('Content-Type')) {
                $request = $request->withHeader('Content-Type', 'application/json');
            }
        }

        $this->config->logger->debug('MailChannels request', [
            'method' => $method,
            'url' => (string) $uri,
        ]);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->config->logger->error('MailChannels transport error', ['error' => $e->getMessage()]);
            throw new TransportException(
                'HTTP transport error while contacting MailChannels: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $this->decode($response);
    }

    /**
     * @param array<string, string> $extraHeaders
     * @return array<string, string>
     */
    private function defaultHeaders(bool $requireApiKey, array $extraHeaders): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => $this->config->resolvedUserAgent(),
        ];

        if ($requireApiKey) {
            $apiKey = $this->config->apiKey;
            if ($apiKey === null || $apiKey === '') {
                throw new ConfigurationException(
                    'MailChannels API key is not configured. Pass apiKey to '
                    . 'MailChannels\\Client or set the MAILCHANNELS_API_KEY environment variable.',
                    errorCode: 'MissingApiKey',
                );
            }
            $headers['X-Api-Key'] = $apiKey;
        }

        foreach ($extraHeaders as $name => $value) {
            $headers[$name] = $value;
        }

        return $headers;
    }

    /** @param array<string, mixed>|null $query */
    private function buildUri(string $path, ?array $query): string
    {
        $url = $this->config->baseUrl . '/' . ltrim($path, '/');
        if ($query === null || $query === []) {
            return $url;
        }
        $serialized = self::serializeQuery($query);
        if ($serialized === '') {
            return $url;
        }
        return $url . (str_contains($url, '?') ? '&' : '?') . $serialized;
    }

    /** @param array<string, mixed> $query */
    private static function serializeQuery(array $query): string
    {
        $pairs = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                $stringValue = implode(',', array_map(
                    static fn (mixed $item): string => self::queryScalar($item),
                    $value,
                ));
            } else {
                $stringValue = self::queryScalar($value);
            }
            $pairs[] = rawurlencode($key) . '=' . rawurlencode($stringValue);
        }
        return implode('&', $pairs);
    }

    private static function queryScalar(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $enumValue = $value->value;
            return is_string($enumValue) ? $enumValue : (string) $enumValue;
        }
        if ($value instanceof UnitEnum) {
            return $value->name;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return $value;
        }
        if ($value instanceof Stringable) {
            return (string) $value;
        }
        throw new ConfigurationException(
            'Unsupported query parameter value type: ' . get_debug_type($value),
            errorCode: 'InvalidQueryValue',
        );
    }

    private function decode(ResponseInterface $response): Response
    {
        $rawBody = (string) $response->getBody();
        /** @var mixed $data */
        $data = null;
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

        $headers = self::collapseHeaders($response);

        $status = $response->getStatusCode();
        if ($status >= 200 && $status < 300) {
            $body = self::normalizeBody($data);
            return new Response($body, $status, $headers, $rawBody);
        }

        $this->raiseFor($status, $data, $headers, $rawBody);
    }

    /** @return array<string, string> */
    private static function collapseHeaders(ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[(string) $name] = implode(', ', $values);
        }
        return $headers;
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>
     */
    private static function normalizeBody($data): array
    {
        if (!is_array($data)) {
            return $data === null ? [] : ['data' => $data];
        }
        if (!self::isAssoc($data)) {
            return ['data' => $data];
        }
        $assoc = [];
        foreach ($data as $key => $value) {
            $assoc[(string) $key] = $value;
        }
        return $assoc;
    }

    /** @param array<int|string, mixed> $array */
    private static function isAssoc(array $array): bool
    {
        return $array === [] || !array_is_list($array);
    }

    /**
     * @param mixed                $data
     * @param array<string,string> $headers
     */
    private function raiseFor(int $status, $data, array $headers, string $rawBody): never
    {
        $message = self::extractMessage($data, $rawBody, $status);
        $code = is_array($data) && isset($data['code']) && is_string($data['code']) ? $data['code'] : null;

        $this->config->logger->error('MailChannels API error', [
            'status' => $status,
            'message' => $message,
            'code' => $code,
        ]);

        $class = match (true) {
            $status === 401 => AuthenticationException::class,
            $status === 403 => ForbiddenException::class,
            $status === 404 => NotFoundException::class,
            $status === 409 => ConflictException::class,
            $status === 413 => PayloadTooLargeException::class,
            $status === 429 => RateLimitException::class,
            $status === 502 => BadGatewayException::class,
            $status >= 500 => ServerException::class,
            $status >= 400 => InvalidRequestException::class,
            default => ApiException::class,
        };

        throw new $class(
            $message,
            statusCode: $status,
            errorCode: $code,
            headers: $headers,
            responseBody: $data ?? $rawBody,
        );
    }

    /** @param mixed $data */
    private static function extractMessage($data, string $rawBody, int $status): string
    {
        if (is_array($data)) {
            foreach (['message', 'error', 'detail', 'title'] as $key) {
                $value = $data[$key] ?? null;
                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }
        $trimmed = trim($rawBody);
        if ($trimmed !== '' && strtolower($trimmed) !== 'null') {
            return $trimmed;
        }
        return "MailChannels API request failed with status {$status}.";
    }
}
