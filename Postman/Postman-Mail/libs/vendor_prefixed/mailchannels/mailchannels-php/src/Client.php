<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels;

use PostSMTP\Vendor\MailChannels\Exception\ConfigurationException;
use PostSMTP\Vendor\MailChannels\Resource\CheckDomain;
use PostSMTP\Vendor\MailChannels\Resource\CustomTrackingDomains;
use PostSMTP\Vendor\MailChannels\Resource\Dkim;
use PostSMTP\Vendor\MailChannels\Resource\Emails;
use PostSMTP\Vendor\MailChannels\Resource\Metrics;
use PostSMTP\Vendor\MailChannels\Resource\SubAccounts;
use PostSMTP\Vendor\MailChannels\Resource\Suppressions;
use PostSMTP\Vendor\MailChannels\Resource\Usage;
use PostSMTP\Vendor\MailChannels\Resource\Webhooks;
use PostSMTP\Vendor\Psr\Http\Client\ClientInterface;
use PostSMTP\Vendor\Psr\Http\Message\RequestFactoryInterface;
use PostSMTP\Vendor\Psr\Http\Message\StreamFactoryInterface;
use PostSMTP\Vendor\Psr\Log\LoggerInterface;

/**
 * MailChannels API client. Resources are exposed as readonly properties:
 *
 *   $client = new MailChannels\Client(apiKey: 'mc-...');
 *   $client->emails->send([...]);
 *   $client->subAccounts->create(companyName: 'ACME', handle: 'tenant42');
 *
 * For full control over base URL, HTTP client, factories, and logging,
 * build a {@see Config} explicitly and pass it through {@see self::fromConfig()}.
 */
final class Client
{
    public readonly Emails $emails;
    public readonly SubAccounts $subAccounts;
    public readonly Metrics $metrics;
    public readonly CheckDomain $checkDomain;
    public readonly Dkim $dkim;
    public readonly CustomTrackingDomains $customTrackingDomains;
    public readonly Suppressions $suppressions;
    public readonly Webhooks $webhooks;
    public readonly Usage $usage;

    private Config $config;
    private Transport $transport;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoggerInterface $logger = null,
        string $userAgent = '',
        ?Config $config = null,
    ) {
        if ($config !== null) {
            self::assertNoConvenienceOptions(
                $apiKey,
                $baseUrl,
                $httpClient,
                $requestFactory,
                $streamFactory,
                $logger,
                $userAgent,
            );
            $this->setConfig($config);
        } else {
            $this->setConfig(new Config(
                apiKey: $apiKey,
                baseUrl: $baseUrl,
                httpClient: $httpClient,
                requestFactory: $requestFactory,
                streamFactory: $streamFactory,
                logger: $logger,
                userAgent: $userAgent,
            ));
        }

        $this->emails = new Emails($this);
        $this->subAccounts = new SubAccounts($this);
        $this->metrics = new Metrics($this);
        $this->checkDomain = new CheckDomain($this);
        $this->dkim = new Dkim($this);
        $this->customTrackingDomains = new CustomTrackingDomains($this);
        $this->suppressions = new Suppressions($this);
        $this->webhooks = new Webhooks($this);
        $this->usage = new Usage($this);
    }

    /**
     * Build a client from a pre-constructed {@see Config}. Use this when
     * the convenience-constructor's named arguments aren't enough — for
     * example when a DI container hands you a configured Config singleton.
     */
    public static function fromConfig(Config $config): self
    {
        return new self(config: $config);
    }

    /** @internal Resources reach in to issue requests; not for application use. */
    public function config(): Config
    {
        return $this->config;
    }

    /** @internal Resources reach in to issue requests; not for application use. */
    public function transport(): Transport
    {
        return $this->transport;
    }

    private function setConfig(Config $config): void
    {
        $this->config = $config;
        $this->transport = new Transport($config);
    }

    private static function assertNoConvenienceOptions(
        ?string $apiKey,
        ?string $baseUrl,
        ?ClientInterface $httpClient,
        ?RequestFactoryInterface $requestFactory,
        ?StreamFactoryInterface $streamFactory,
        ?LoggerInterface $logger,
        string $userAgent,
    ): void {
        if (
            $apiKey !== null
            || $baseUrl !== null
            || $httpClient !== null
            || $requestFactory !== null
            || $streamFactory !== null
            || $logger !== null
            || $userAgent !== ''
        ) {
            throw new ConfigurationException('Pass either `config` or individual Client constructor options, not both.');
        }
    }
}
