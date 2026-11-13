<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Emails::send()}.
 *
 * Covers two distinct success shapes from `/send`:
 *
 * - **Normal send (HTTP 202).** Body is a `SendResults` envelope:
 *   `{ request_id, results: [SendResult] }`. The per-personalization
 *   message ids live inside `$results[i]->messageId` — `/send` does
 *   not return a top-level `message_id`.
 * - **Dry run (HTTP 200).** Body is a `Message`: `{ data: [string, ...] }`
 *   where each entry is a rendered RFC822 message blob, one per
 *   personalization. Exposed as `$dryRunData`.
 *
 * All typed fields are nullable because the API omits a given field
 * whenever it doesn't apply to that response shape — branch on which
 * one is non-null.
 *
 * Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
 * helpers — `$response['anything']`, `$response->statusCode`,
 * `$response->headers`, and `$response->requestId()` all keep working.
 * `requestId()` is overridden here to check the response body's
 * `request_id` field before falling back to header inspection.
 */
final class SendResponse extends Response
{
    /** @var list<SendResult>|null */
    public readonly ?array $results;

    /** @var list<string>|null */
    public readonly ?array $dryRunData;

    /**
     * Build a SendResponse from a generic {@see Response}, validating
     * field types as it goes.
     *
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are not errors — they're nulled out
     *     so callers can branch on `dryRunData !== null` etc.
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
        $rawResults = ResponseValidator::asNullableListOfArrays($body, 'results');
        $this->results = $rawResults === null
            ? null
            : array_map(
                static fn (array $r): SendResult => SendResult::fromArray($r),
                $rawResults,
            );
        $this->dryRunData = ResponseValidator::asNullableListOfStrings($body, 'data');
    }

    public function requestId(): ?string
    {
        $bodyValue = $this->data['request_id'] ?? null;
        if (is_string($bodyValue) && $bodyValue !== '') {
            return $bodyValue;
        }
        return parent::requestId();
    }
}
