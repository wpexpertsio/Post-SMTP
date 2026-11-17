<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * Duration of a webhook batch attempt — how long the receiving endpoint
 * took to respond. Spec schema: `WebhookBatchDuration`. `$unit` is
 * always `"milliseconds"` today but is exposed as a string for forward-
 * compat in case the API ever adds other units.
 *
 * Plain value object — not a `Response` subclass.
 */
final class WebhookBatchDuration implements JsonSerializable
{
    public function __construct(
        public readonly ?int $value = null,
        public readonly ?string $unit = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @throws ResponseValidationException when a present field has the
     *     wrong type.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            value: ResponseValidator::asNullableInt($data, 'value'),
            unit: ResponseValidator::asNullableString($data, 'unit'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->value !== null) {
            $out['value'] = $this->value;
        }
        if ($this->unit !== null) {
            $out['unit'] = $this->unit;
        }
        return $out;
    }
}
