<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * The HTTP response MailChannels received from a customer's webhook
 * endpoint during a validation test. Spec schema: `WebhookResponse`
 * (renamed in the SDK to avoid colliding with the SDK's own
 * {@see \MailChannels\Response}).
 *
 * A null status indicates no response was received (timeout, connection
 * failure, etc.).
 *
 * Plain value object — not a `Response` subclass.
 */
final class WebhookEndpointResponse implements JsonSerializable
{
    public function __construct(
        public readonly ?int $status = null,
        public readonly ?string $body = null,
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
            status: ResponseValidator::asNullableInt($data, 'status'),
            body: ResponseValidator::asNullableString($data, 'body'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->status !== null) {
            $out['status'] = $this->status;
        }
        if ($this->body !== null) {
            $out['body'] = $this->body;
        }
        return $out;
    }
}
