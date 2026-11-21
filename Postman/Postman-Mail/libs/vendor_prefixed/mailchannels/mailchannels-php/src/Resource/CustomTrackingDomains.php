<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Enum\CustomTrackingScope;
use PostSMTP\Vendor\MailChannels\Enum\CustomTrackingStatus;
use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\CustomTrackingDomainListResponse;
use PostSMTP\Vendor\MailChannels\Response\CustomTrackingDomainResponse;

/**
 * Register and manage custom tracking domains — branded hostnames that
 * replace MailChannels' shared domains for click tracking, open tracking,
 * or unsubscribe links. See `docs/CUSTOM_TRACKING.md` for the DNS
 * verification flow.
 */
final class CustomTrackingDomains extends AbstractResource
{
    public function list(
        ?string $name = null,
        CustomTrackingStatus|string|null $status = null,
        CustomTrackingScope|string|null $scope = null,
        ?int $limit = null,
        ?int $offset = null,
    ): CustomTrackingDomainListResponse {
        $query = self::compactOrNull([
            'name' => $name,
            'status' => $status instanceof CustomTrackingStatus ? $status->value : $status,
            'scope' => $scope instanceof CustomTrackingScope ? $scope->value : $scope,
            'limit' => $limit,
            'offset' => $offset,
        ]);
        return new CustomTrackingDomainListResponse(
            $this->request('GET', '/custom-tracking-domains', null, $query),
        );
    }

    public function create(
        string $name,
        string $hostname,
        CustomTrackingScope|string $scope,
    ): CustomTrackingDomainResponse {
        $scopeValue = $scope instanceof CustomTrackingScope ? $scope->value : $scope;
        $this->client->config()->logger->info('Registering custom tracking domain', [
            'name' => $name,
            'hostname' => $hostname,
            'scope' => $scopeValue,
        ]);
        return new CustomTrackingDomainResponse(
            $this->request('POST', '/custom-tracking-domains', [
                'name' => $name,
                'hostname' => $hostname,
                'scope' => $scopeValue,
            ]),
        );
    }

    public function update(
        string $hostname,
        CustomTrackingScope|string $scope,
        ?string $name = null,
        CustomTrackingStatus|string|null $status = null,
    ): CustomTrackingDomainResponse {
        $scopeValue = $scope instanceof CustomTrackingScope ? $scope->value : $scope;
        $statusValue = $status instanceof CustomTrackingStatus ? $status->value : $status;
        $payload = self::compact([
            'name' => $name,
            'status' => $statusValue,
        ]);
        $this->client->config()->logger->info('Updating custom tracking domain', [
            'hostname' => $hostname,
            'scope' => $scopeValue,
            'name' => $name,
            'status' => $statusValue,
        ]);
        $path = self::encodedPath('custom-tracking-domains', $hostname, $scopeValue);
        return new CustomTrackingDomainResponse(
            $this->request('PATCH', $path, $payload),
        );
    }

    public function delete(string $hostname, CustomTrackingScope|string $scope): Response
    {
        $scopeValue = $scope instanceof CustomTrackingScope ? $scope->value : $scope;
        $this->client->config()->logger->info('Deleting custom tracking domain', [
            'hostname' => $hostname,
            'scope' => $scopeValue,
        ]);
        $path = self::encodedPath('custom-tracking-domains', $hostname, $scopeValue);
        return $this->request('DELETE', $path);
    }
}
