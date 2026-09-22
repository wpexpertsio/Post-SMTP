<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One entry in the `MetricsSenderResponse::$senders` list — a per-sender
 * (campaign or sub-account, depending on `sender_type`) rollup of message
 * outcomes during the queried time range.
 *
 * Plain value object — not a `Response` subclass.
 */
final class MetricsSender implements JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $processed = null,
        public readonly ?int $delivered = null,
        public readonly ?int $bounced = null,
        public readonly ?int $dropped = null,
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
            name: ResponseValidator::asNullableString($data, 'name'),
            processed: ResponseValidator::asNullableInt($data, 'processed'),
            delivered: ResponseValidator::asNullableInt($data, 'delivered'),
            bounced: ResponseValidator::asNullableInt($data, 'bounced'),
            dropped: ResponseValidator::asNullableInt($data, 'dropped'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->name !== null) {
            $out['name'] = $this->name;
        }
        if ($this->processed !== null) {
            $out['processed'] = $this->processed;
        }
        if ($this->delivered !== null) {
            $out['delivered'] = $this->delivered;
        }
        if ($this->bounced !== null) {
            $out['bounced'] = $this->bounced;
        }
        if ($this->dropped !== null) {
            $out['dropped'] = $this->dropped;
        }
        return $out;
    }
}
