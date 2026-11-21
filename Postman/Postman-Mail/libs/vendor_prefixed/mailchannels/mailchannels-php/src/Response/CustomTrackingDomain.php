<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * A registered MailChannels custom tracking domain. Returned inside a list
 * by {@see \MailChannels\Resource\CustomTrackingDomains::list()}.
 *
 * Plain value object — not a `Response` subclass. See
 * {@see CustomTrackingDomainResponse} for the shape returned by
 * `create()`/`update()`, which may represent a domain still pending DNS
 * verification rather than one of these fully-registered domains.
 */
final class CustomTrackingDomain implements JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $hostname = null,
        public readonly ?string $scope = null,
        public readonly ?string $status = null,
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
            name: ResponseValidator::asNullableString($data, 'name'),
            hostname: ResponseValidator::asNullableString($data, 'hostname'),
            scope: ResponseValidator::asNullableString($data, 'scope'),
            status: ResponseValidator::asNullableString($data, 'status'),
            createdAt: ResponseValidator::asNullableString($data, 'created_at'),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->name !== null) {
            $out['name'] = $this->name;
        }
        if ($this->hostname !== null) {
            $out['hostname'] = $this->hostname;
        }
        if ($this->scope !== null) {
            $out['scope'] = $this->scope;
        }
        if ($this->status !== null) {
            $out['status'] = $this->status;
        }
        if ($this->createdAt !== null) {
            $out['created_at'] = $this->createdAt;
        }
        return $out;
    }
}
