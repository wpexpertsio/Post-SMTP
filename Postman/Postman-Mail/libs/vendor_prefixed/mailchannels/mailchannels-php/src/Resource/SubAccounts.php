<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Client;
use PostSMTP\Vendor\MailChannels\Exception\ValidationException;
use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\SubAccountListResponse;
use PostSMTP\Vendor\MailChannels\Response\SubAccountResponse;
use PostSMTP\Vendor\MailChannels\Response\UsageResponse;

final class SubAccounts extends AbstractResource
{
    /**
     * Handles assigned to sub-accounts must contain only lowercase letters
     * and digits, with length between {@see self::HANDLE_MIN_LENGTH} and
     * {@see self::HANDLE_MAX_LENGTH}, per the Email API contract.
     */
    public const HANDLE_PATTERN = '/^[a-z0-9]+$/';
    public const HANDLE_MIN_LENGTH = 3;
    public const HANDLE_MAX_LENGTH = 128;

    public const COMPANY_NAME_MIN_LENGTH = 3;
    public const COMPANY_NAME_MAX_LENGTH = 128;

    public readonly SubAccountApiKeys $apiKeys;
    public readonly SubAccountSmtpPasswords $smtpPasswords;
    public readonly SubAccountLimits $limits;

    public function __construct(Client $client)
    {
        parent::__construct($client);
        $this->apiKeys = new SubAccountApiKeys($client);
        $this->smtpPasswords = new SubAccountSmtpPasswords($client);
        $this->limits = new SubAccountLimits($client);
    }

    /**
     * Create a sub-account. `companyName` is required by the API.
     * `handle` is optional — if omitted, MailChannels assigns one. When
     * provided, it must match {@see self::HANDLE_PATTERN}.
     */
    public function create(string $companyName, ?string $handle = null): SubAccountResponse
    {
        self::validateCompanyName($companyName);
        if ($handle !== null) {
            self::validateHandle($handle);
        }
        $payload = self::compact(['company_name' => $companyName, 'handle' => $handle]);
        $this->client->config()->logger->info('Creating sub-account', ['handle' => $handle]);
        return new SubAccountResponse(
            $this->request('POST', '/sub-account', $payload),
        );
    }

    /**
     * Throw if a caller-supplied handle does not match the API contract.
     * Used by {@see self::create()} and exposed publicly for callers that
     * want to validate handles upstream.
     */
    public static function validateHandle(string $handle): void
    {
        $length = strlen($handle);
        if (
            $length < self::HANDLE_MIN_LENGTH
            || $length > self::HANDLE_MAX_LENGTH
            || preg_match(self::HANDLE_PATTERN, $handle) !== 1
        ) {
            throw new ValidationException(
                sprintf(
                    "Sub-account handle '%s' must be %d-%d characters of lowercase letters and digits.",
                    $handle,
                    self::HANDLE_MIN_LENGTH,
                    self::HANDLE_MAX_LENGTH,
                ),
                errorCode: 'InvalidSubAccountHandle',
            );
        }
    }

    /**
     * Throw if a caller-supplied company name is missing or out of range.
     * The Email API requires a non-empty value 3-128 characters in length.
     */
    public static function validateCompanyName(string $companyName): void
    {
        $length = strlen($companyName);
        if ($length < self::COMPANY_NAME_MIN_LENGTH || $length > self::COMPANY_NAME_MAX_LENGTH) {
            throw new ValidationException(
                sprintf(
                    '`companyName` must be %d-%d characters; got %d.',
                    self::COMPANY_NAME_MIN_LENGTH,
                    self::COMPANY_NAME_MAX_LENGTH,
                    $length,
                ),
                errorCode: 'InvalidSubAccountCompanyName',
            );
        }
    }

    public function list(?int $limit = null, ?int $offset = null): SubAccountListResponse
    {
        return new SubAccountListResponse(
            $this->request('GET', '/sub-account', null, self::compactOrNull(['limit' => $limit, 'offset' => $offset])),
        );
    }

    public function retrieveUsage(string $handle): UsageResponse
    {
        self::validateHandle($handle);
        $path = self::encodedPath('sub-account', $handle, 'usage');
        return new UsageResponse($this->request('GET', $path));
    }

    public function suspend(string $handle): Response
    {
        self::validateHandle($handle);
        $this->client->config()->logger->info('Suspending sub-account', ['handle' => $handle]);
        $path = self::encodedPath('sub-account', $handle, 'suspend');
        return $this->request('POST', $path);
    }

    public function activate(string $handle): Response
    {
        self::validateHandle($handle);
        $this->client->config()->logger->info('Activating sub-account', ['handle' => $handle]);
        $path = self::encodedPath('sub-account', $handle, 'activate');
        return $this->request('POST', $path);
    }

    public function delete(string $handle): Response
    {
        self::validateHandle($handle);
        $this->client->config()->logger->info('Deleting sub-account', ['handle' => $handle]);
        $path = self::encodedPath('sub-account', $handle);
        return $this->request('DELETE', $path);
    }
}
