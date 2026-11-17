<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Emails::queue()}
 * for the `/send-async` endpoint. The server queues the message and acks
 * with a request id plus a queued-at timestamp — there is **no** message
 * id at this point because delivery hasn't happened yet. The actual
 * per-message identifiers arrive later via webhook events.
 *
 * Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
 * helpers. `requestId()` is overridden to check the body's `request_id`
 * field first, falling back to header inspection.
 */
final class QueuedSendResponse extends Response
{
    public readonly ?string $queuedAt;

    /**
     * Build a QueuedSendResponse from a generic {@see Response}, validating
     * field types as it goes.
     *
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
        $this->queuedAt = ResponseValidator::asNullableString($body, 'queued_at');
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
