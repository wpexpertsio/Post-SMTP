<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One enrolled webhook subscription entry returned by
 * `GET /webhook`. Spec schema: `Webhook` (renamed in the SDK to avoid
 * confusion with {@see \MailChannels\Resource\Webhooks}).
 *
 * Plain value object — not a `Response` subclass.
 */
final class WebhookSubscription implements JsonSerializable
{
    public function __construct(
        public readonly ?string $webhook = null,
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
            webhook: ResponseValidator::asNullableString($data, 'webhook'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->webhook === null ? [] : ['webhook' => $this->webhook];
    }
}
