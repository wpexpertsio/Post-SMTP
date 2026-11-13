<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One per-personalization result entry returned by `/send`. Spec schema:
 * `SendResult`.
 *
 * Plain value object — not a `Response` subclass. Instances live inside
 * {@see SendResponse::$results}, one per personalization in the original
 * request. `$messageId` is the canonical place to find a message's id —
 * the `/send` endpoint does not return a top-level `message_id`.
 *
 * `$status` carries the spec enum values `'sent'` or `'failed'`. The spec
 * notes that `'sent'` is a temporary status; the terminal status arrives
 * via webhook events.
 */
final class SendResult implements JsonSerializable
{
    public function __construct(
        public readonly ?int $index = null,
        public readonly ?string $messageId = null,
        public readonly ?string $reason = null,
        public readonly ?string $status = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            index: ResponseValidator::asNullableInt($data, 'index'),
            messageId: ResponseValidator::asNullableString($data, 'message_id'),
            reason: ResponseValidator::asNullableString($data, 'reason'),
            status: ResponseValidator::asNullableString($data, 'status'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->index !== null) {
            $out['index'] = $this->index;
        }
        if ($this->messageId !== null) {
            $out['message_id'] = $this->messageId;
        }
        if ($this->reason !== null) {
            $out['reason'] = $this->reason;
        }
        if ($this->status !== null) {
            $out['status'] = $this->status;
        }
        return $out;
    }
}
