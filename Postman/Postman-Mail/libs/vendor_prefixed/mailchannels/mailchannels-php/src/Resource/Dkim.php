<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Enum\DkimAlgorithm;
use PostSMTP\Vendor\MailChannels\Enum\DkimKeyStatus;
use PostSMTP\Vendor\MailChannels\Enum\DkimUpdateStatus;
use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\DkimKeyListResponse;
use PostSMTP\Vendor\MailChannels\Response\DkimKeyResponse;
use PostSMTP\Vendor\MailChannels\Response\DkimRotateResponse;

final class Dkim extends AbstractResource
{
    public function create(
        string $domain,
        string $selector,
        DkimAlgorithm|string|null $algorithm = null,
        ?int $keyLength = null,
    ): DkimKeyResponse {
        $payload = self::compact([
            'selector' => $selector,
            'algorithm' => $algorithm instanceof DkimAlgorithm ? $algorithm->value : $algorithm,
            'key_length' => $keyLength,
        ]);
        $this->client->config()->logger->info('Creating DKIM key pair', ['domain' => $domain, 'selector' => $selector]);
        $path = self::encodedPath('domains', $domain, 'dkim-keys');
        return new DkimKeyResponse(
            $this->request('POST', $path, $payload),
        );
    }

    public function list(
        string $domain,
        ?string $selector = null,
        DkimKeyStatus|string|null $status = null,
        ?int $offset = null,
        ?int $limit = null,
        ?bool $includeDnsRecord = null,
    ): DkimKeyListResponse {
        $query = self::compactOrNull([
            'selector' => $selector,
            'status' => $status instanceof DkimKeyStatus ? $status->value : $status,
            'offset' => $offset,
            'limit' => $limit,
            'include_dns_record' => $includeDnsRecord,
        ]);
        $path = self::encodedPath('domains', $domain, 'dkim-keys');
        return new DkimKeyListResponse(
            $this->request('GET', $path, null, $query),
        );
    }

    public function updateStatus(string $domain, string $selector, DkimUpdateStatus|string $status): Response
    {
        $statusValue = $status instanceof DkimUpdateStatus ? $status->value : $status;
        $this->client->config()->logger->info('Updating DKIM key status', [
            'domain' => $domain,
            'selector' => $selector,
            'status' => $statusValue,
        ]);
        $path = self::encodedPath('domains', $domain, 'dkim-keys', $selector);
        return $this->request('PATCH', $path, ['status' => $statusValue]);
    }

    public function rotate(string $domain, string $selector, string $newSelector): DkimRotateResponse
    {
        $this->client->config()->logger->info('Rotating DKIM key', [
            'domain' => $domain,
            'selector' => $selector,
            'new_selector' => $newSelector,
        ]);
        $path = self::encodedPath('domains', $domain, 'dkim-keys', $selector, 'rotate');
        return new DkimRotateResponse(
            $this->request(
                'POST',
                $path,
                ['new_key' => ['selector' => $newSelector]],
            ),
        );
    }
}
