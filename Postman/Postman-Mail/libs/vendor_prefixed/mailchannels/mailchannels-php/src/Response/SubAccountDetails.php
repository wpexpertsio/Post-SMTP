<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One sub-account record. Spec schema: `SubAccountDetails`.
 *
 * Plain value object — not a `Response` subclass. Returned inside
 * {@see SubAccountListResponse::$subAccounts} or as
 * {@see SubAccountResponse::$subAccount}.
 */
final class SubAccountDetails implements JsonSerializable
{
    public function __construct(
        public readonly ?string $handle = null,
        public readonly ?string $companyName = null,
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
            handle: ResponseValidator::asNullableString($data, 'handle'),
            companyName: ResponseValidator::asNullableString($data, 'company_name'),
            enabled: ResponseValidator::asNullableBool($data, 'enabled'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->handle !== null) {
            $out['handle'] = $this->handle;
        }
        if ($this->companyName !== null) {
            $out['company_name'] = $this->companyName;
        }
        if ($this->enabled !== null) {
            $out['enabled'] = $this->enabled;
        }
        return $out;
    }
}
