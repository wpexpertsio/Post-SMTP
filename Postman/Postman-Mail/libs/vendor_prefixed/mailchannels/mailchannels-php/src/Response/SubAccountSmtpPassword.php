<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One sub-account SMTP password record. Spec schema: `SMTPPassword`
 * (renamed for consistency with PHP class-name conventions).
 *
 * `$smtpPassword` carries the raw password and is only returned by the
 * create endpoint — the list endpoint omits the secret. Treat it as a
 * credential.
 *
 * Plain value object — not a `Response` subclass.
 */
final class SubAccountSmtpPassword implements JsonSerializable
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $smtpPassword = null,
        public readonly ?bool $enabled = null,
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
            smtpPassword: ResponseValidator::asNullableString($data, 'smtp_password'),
            enabled: ResponseValidator::asNullableBool($data, 'enabled'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->id !== null) {
            $out['id'] = $this->id;
        }
        if ($this->smtpPassword !== null) {
            $out['smtp_password'] = $this->smtpPassword;
        }
        if ($this->enabled !== null) {
            $out['enabled'] = $this->enabled;
        }
        return $out;
    }
}
