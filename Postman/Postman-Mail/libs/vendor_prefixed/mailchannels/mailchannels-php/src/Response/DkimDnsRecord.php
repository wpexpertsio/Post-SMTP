<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One DKIM DNS record suggestion returned by MailChannels — the TXT
 * record you need to publish in your authoritative DNS for the
 * selector to validate.
 *
 * Plain value object — not a `Response` subclass. Instances live inside
 * {@see DkimKeyInfo::$dkimDnsRecords}.
 */
final class DkimDnsRecord implements JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?string $value = null,
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
            type: ResponseValidator::asNullableString($data, 'type'),
            value: ResponseValidator::asNullableString($data, 'value'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->name !== null) {
            $out['name'] = $this->name;
        }
        if ($this->type !== null) {
            $out['type'] = $this->type;
        }
        if ($this->value !== null) {
            $out['value'] = $this->value;
        }
        return $out;
    }
}
