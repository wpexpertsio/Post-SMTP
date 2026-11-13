<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One suppression list entry returned by `/suppression-list`.
 *
 * Plain value object — not a `Response` subclass. Instances live inside
 * {@see SuppressionListResponse::$suppressionList} and are constructed by
 * that class's constructor.
 */
final class SuppressionEntry implements JsonSerializable
{
    /** @param list<string>|null $suppressionTypes */
    public function __construct(
        public readonly ?string $recipient = null,
        public readonly ?array $suppressionTypes = null,
        public readonly ?string $notes = null,
        public readonly ?string $source = null,
        public readonly ?string $sender = null,
        public readonly ?string $createdAt = null,
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
            recipient: ResponseValidator::asNullableString($data, 'recipient'),
            suppressionTypes: ResponseValidator::asNullableListOfStrings($data, 'suppression_types'),
            notes: ResponseValidator::asNullableString($data, 'notes'),
            source: ResponseValidator::asNullableString($data, 'source'),
            sender: ResponseValidator::asNullableString($data, 'sender'),
            createdAt: ResponseValidator::asNullableString($data, 'created_at'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->recipient !== null) {
            $out['recipient'] = $this->recipient;
        }
        if ($this->suppressionTypes !== null) {
            $out['suppression_types'] = $this->suppressionTypes;
        }
        if ($this->notes !== null) {
            $out['notes'] = $this->notes;
        }
        if ($this->source !== null) {
            $out['source'] = $this->source;
        }
        if ($this->sender !== null) {
            $out['sender'] = $this->sender;
        }
        if ($this->createdAt !== null) {
            $out['created_at'] = $this->createdAt;
        }
        return $out;
    }
}
