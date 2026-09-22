<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels;

use PostSMTP\Vendor\Psr\Http\Client\ClientInterface;
use PostSMTP\Vendor\Psr\Http\Message\RequestFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\StreamFactoryInterface;
use PostSMTP\Vendor\Psr\Log\LoggerInterface;
use PostSMTP\Vendor\Psr\Log\NullLogger;

final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.mailchannels.net/tx/v1';

    public readonly ?string $apiKey;
    public readonly string $baseUrl;
    public readonly LoggerInterface $logger;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        public readonly ?ClientInterface $httpClient = null,
        public readonly ?RequestFactoryInterface $requestFactory = null,
        public readonly ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null,
        public readonly string $userAgent = '',
    ) {
        $this->apiKey = $apiKey ?? self::resolveApiKey();
        $this->baseUrl = rtrim($baseUrl ?? self::resolveBaseUrl(), '/');
        $this->logger = $logger ?? new NullLogger();
    }

    public function withApiKey(string $apiKey): self
    {
        return new self(
            apiKey: $apiKey,
            baseUrl: $this->baseUrl,
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            logger: $this->logger,
            userAgent: $this->userAgent,
        );
    }

    public function resolvedUserAgent(): string
    {
        return $this->userAgent !== '' ? $this->userAgent : Version::userAgent();
    }

    private static function resolveApiKey(): ?string
    {
        $fromEnv = getenv('MAILCHANNELS_API_KEY');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }
        return null;
    }

    private static function resolveBaseUrl(): string
    {
        $fromEnv = getenv('MAILCHANNELS_API_URL');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }
        return self::DEFAULT_BASE_URL;
    }
}
