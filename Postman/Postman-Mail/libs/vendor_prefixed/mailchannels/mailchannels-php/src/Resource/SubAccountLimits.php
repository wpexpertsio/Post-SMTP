<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Exception\ValidationException;
use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\SubAccountLimitResponse;

final class SubAccountLimits extends AbstractResource
{
    /**
     * Set the monthly sending limit for a sub-account. Either `sends` or its
     * documented alias `monthlyLimit` must be provided; the request payload
     * uses `sends`.
     */
    public function set(string $handle, ?int $sends = null, ?int $monthlyLimit = null): SubAccountLimitResponse
    {
        SubAccounts::validateHandle($handle);
        $resolved = $sends ?? $monthlyLimit;
        if ($resolved === null) {
            throw new ValidationException(
                'SubAccountLimits::set() requires `sends` or `monthlyLimit`.',
                errorCode: 'InvalidLimitPayload',
            );
        }
        if ($resolved < 0) {
            throw new ValidationException(
                "Sub-account `sends` limit must be >= 0; got {$resolved}.",
                errorCode: 'InvalidLimitPayload',
            );
        }
        $this->client->config()->logger->info('Setting sub-account limit', ['handle' => $handle, 'sends' => $resolved]);
        $path = self::encodedPath('sub-account', $handle, 'limit');
        return new SubAccountLimitResponse(
            $this->request('PUT', $path, ['sends' => $resolved]),
        );
    }

    public function retrieve(string $handle): SubAccountLimitResponse
    {
        SubAccounts::validateHandle($handle);
        $path = self::encodedPath('sub-account', $handle, 'limit');
        return new SubAccountLimitResponse(
            $this->request('GET', $path),
        );
    }

    public function delete(string $handle): Response
    {
        SubAccounts::validateHandle($handle);
        $path = self::encodedPath('sub-account', $handle, 'limit');
        return $this->request('DELETE', $path);
    }
}
