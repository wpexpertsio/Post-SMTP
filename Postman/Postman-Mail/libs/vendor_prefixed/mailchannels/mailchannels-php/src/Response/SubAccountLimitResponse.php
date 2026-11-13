<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for both `GET /sub-account/{handle}/limit` (returns
 * `Limit`: `{"sends": int}`) and `PUT /sub-account/{handle}/limit`
 * (returns `LimitUpdateResult`: `{"limit": {"sends": int}}`).
 *
 * The two endpoints have semantically equivalent payloads but
 * structurally different shapes — GET returns the limit fields at the
 * top level, PUT wraps them under a `limit` key. The constructor
 * unwraps the PUT shape transparently so callers see the same typed
 * `$sends` property in both cases.
 */
final class SubAccountLimitResponse extends Response
{
    public readonly ?int $sends;

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type.
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
        // The PUT response wraps the limit fields under a `limit` key
        // (LimitUpdateResult schema). The GET response inlines them
        // directly (Limit schema). Unwrap transparently.
        $limitData = ResponseValidator::asNullableAssocArray($body, 'limit') ?? $body;
        $this->sends = ResponseValidator::asNullableInt($limitData, 'sends');
    }
}
