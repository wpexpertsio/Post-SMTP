<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One sub-account API key record. Spec schema: `APIKey` (renamed to
 * `SubAccountApiKey` to follow PHP conventions and avoid a one-token
 * top-level class name).
 *
 * `$key` is the raw API key string and is only returned by the create
 * endpoint — the list endpoint omits the secret material. Treat it as
 * a credential.
 *
 * Plain value object — not a `Response` subclass.
 */
final class SubAccountApiKey implements JsonSerializable
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $key = null,
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
            id: ResponseValidator::asNullableInt($data, 'id'),
            key: ResponseValidator::asNullableString($data, 'key'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->id !== null) {
            $out['id'] = $this->id;
        }
        if ($this->key !== null) {
            $out['key'] = $this->key;
        }
        return $out;
    }
}
