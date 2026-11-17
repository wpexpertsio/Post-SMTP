<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Usage::retrieve()}.
 *
 * `$totalUsage` is the count of accepted send-requests for the current
 * billing period; `$periodStartDate` / `$periodEndDate` bracket the period
 * the count covers. `$monthlyLimit` is the effective send cap for that
 * period — a value of `0` means the account cannot send; for a sub-account
 * with no explicit limit set, this reports the parent account's limit
 * rather than `-1` (contrast {@see \MailChannels\Resource\SubAccountLimits::retrieve()},
 * which does return `-1` in that case). All fields are nullable — missing
 * fields stay null so the SDK stays forward-compatible with future API
 * additions.
 *
 * Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
 * helpers.
 */
final class UsageResponse extends Response
{
    public readonly ?int $totalUsage;
    public readonly ?string $periodStartDate;
    public readonly ?string $periodEndDate;
    public readonly ?int $monthlyLimit;

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
     */
    public function __construct(Response $response)
    {
        $body = $response->data;
        parent::__construct(
            $response->data,
            $response->statusCode,
            $response->headers,
            $response->rawBody,
        );
        $this->totalUsage = ResponseValidator::asNullableInt($body, 'total_usage');
        $this->periodStartDate = ResponseValidator::asNullableString($body, 'period_start_date');
        $this->periodEndDate = ResponseValidator::asNullableString($body, 'period_end_date');
        $this->monthlyLimit = ResponseValidator::asNullableInt($body, 'monthly_limit');
    }
}
