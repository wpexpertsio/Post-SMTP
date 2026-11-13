<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\SubAccountApiKeyListResponse;
use PostSMTP\Vendor\MailChannels\Response\SubAccountApiKeyResponse;

final class SubAccountApiKeys extends AbstractResource
{
    public function create(string $handle): SubAccountApiKeyResponse
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Creating sub-account API key', ['handle' => $handle]);
        $path = self::encodedPath('sub-account', $handle, 'api-key');
        return new SubAccountApiKeyResponse(
            $this->request('POST', $path),
        );
    }

    public function list(string $handle): SubAccountApiKeyListResponse
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Listing sub-account API keys', ['handle' => $handle]);
        $path = self::encodedPath('sub-account', $handle, 'api-key');
        return new SubAccountApiKeyListResponse(
            $this->request('GET', $path),
        );
    }

    public function delete(string $handle, string|int $keyId): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Deleting sub-account API key', ['handle' => $handle, 'key_id' => $keyId]);
        $path = self::encodedPath('sub-account', $handle, 'api-key', $keyId);
        return $this->request('DELETE', $path);
    }
}
