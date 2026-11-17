<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One webhook batch record returned in
 * {@see WebhookBatchListResponse::$webhookBatches}. Spec schema:
 * `WebhookBatch`.
 *
 * `$status` carries the response-form enum (`1xx_response` /
 * `2xx_response` / ... / `no_response`) — distinct from the query-form
 * filter used by {@see \MailChannels\Enum\WebhookBatchStatus} (which
 * lacks the `_response` suffix). Exposed as a plain string here to
 * mirror the wire value exactly.
 *
 * Plain value object — not a `Response` subclass.
 */
final class WebhookBatch implements JsonSerializable
{
    public function __construct(
        public readonly ?int $batchId = null,
        public readonly ?string $customerHandle = null,
        public readonly ?string $webhook = null,
        public readonly ?string $status = null,
        public readonly ?int $statusCode = null,
        public readonly ?int $eventCount = null,
        public readonly ?string $createdAt = null,
        public readonly ?WebhookBatchDuration $duration = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @throws ResponseValidationException when a present field has the
     *     wrong type.
     */
    public static function fromArray(array $data): self
    {
        $durationData = ResponseValidator::asNullableAssocArray($data, 'duration');

        return new self(
            batchId: ResponseValidator::asNullableInt($data, 'batch_id'),
            customerHandle: ResponseValidator::asNullableString($data, 'customer_handle'),
            webhook: ResponseValidator::asNullableString($data, 'webhook'),
            status: ResponseValidator::asNullableString($data, 'status'),
            statusCode: ResponseValidator::asNullableInt($data, 'status_code'),
            eventCount: ResponseValidator::asNullableInt($data, 'event_count'),
            createdAt: ResponseValidator::asNullableString($data, 'created_at'),
            duration: $durationData === null ? null : WebhookBatchDuration::fromArray($durationData),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->batchId !== null) {
            $out['batch_id'] = $this->batchId;
        }
        if ($this->customerHandle !== null) {
            $out['customer_handle'] = $this->customerHandle;
        }
        if ($this->webhook !== null) {
            $out['webhook'] = $this->webhook;
        }
        if ($this->status !== null) {
            $out['status'] = $this->status;
        }
        if ($this->statusCode !== null) {
            $out['status_code'] = $this->statusCode;
        }
        if ($this->eventCount !== null) {
            $out['event_count'] = $this->eventCount;
        }
        if ($this->createdAt !== null) {
            $out['created_at'] = $this->createdAt;
        }
        if ($this->duration !== null) {
            $out['duration'] = $this->duration;
        }
        return $out;
    }
}
