<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `POST /webhook-batch/{batch_id}/resend`. Spec
 * schema: `WebhookResendResponse`.
 *
 * Similar in shape to {@see WebhookBatch} but flatter: duration is
 * inlined as `$durationInMs` (nullable int when no response was
 * received) instead of a nested object, and there's no top-level
 * `status` enum.
 *
 * Note: the body field `status_code` (the HTTP status returned by the
 * customer's webhook endpoint during the resend) is exposed as
 * `$webhookStatusCode` rather than `$statusCode` to avoid shadowing
 * the inherited {@see Response::$statusCode} (which is MailChannels'
 * own HTTP response status for this SDK call).
 */
final class WebhookResendResponse extends Response
{
    public readonly ?int $batchId;
    public readonly ?string $customerHandle;
    public readonly ?string $webhook;
    public readonly ?int $webhookStatusCode;
    public readonly ?int $eventCount;
    public readonly ?int $durationInMs;
    public readonly ?string $createdAt;

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
        $this->batchId = ResponseValidator::asNullableInt($body, 'batch_id');
        $this->customerHandle = ResponseValidator::asNullableString($body, 'customer_handle');
        $this->webhook = ResponseValidator::asNullableString($body, 'webhook');
        $this->webhookStatusCode = ResponseValidator::asNullableInt($body, 'status_code');
        $this->eventCount = ResponseValidator::asNullableInt($body, 'event_count');
        $this->durationInMs = ResponseValidator::asNullableInt($body, 'duration_in_ms');
        $this->createdAt = ResponseValidator::asNullableString($body, 'created_at');
    }
}
