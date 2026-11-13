<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\SubAccountSmtpPasswordListResponse;
use PostSMTP\Vendor\MailChannels\Response\SubAccountSmtpPasswordResponse;

final class SubAccountSmtpPasswords extends AbstractResource
{
    public function create(string $handle): SubAccountSmtpPasswordResponse
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Creating sub-account SMTP password', ['handle' => $handle]);
        $path = self::encodedPath('sub-account', $handle, 'smtp-password');
        return new SubAccountSmtpPasswordResponse(
            $this->request('POST', $path),
        );
    }

    public function list(string $handle): SubAccountSmtpPasswordListResponse
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Listing sub-account SMTP passwords', ['handle' => $handle]);
        $path = self::encodedPath('sub-account', $handle, 'smtp-password');
        return new SubAccountSmtpPasswordListResponse(
            $this->request('GET', $path),
        );
    }

    public function delete(string $handle, string|int $passwordId): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Deleting sub-account SMTP password', ['handle' => $handle, 'password_id' => $passwordId]);
        $path = self::encodedPath('sub-account', $handle, 'smtp-password', $passwordId);
        return $this->request('DELETE', $path);
    }
}
