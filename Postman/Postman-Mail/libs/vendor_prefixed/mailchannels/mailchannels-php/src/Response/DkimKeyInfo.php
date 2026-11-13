<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * MailChannels-hosted DKIM key metadata. Returned (inside a list or pair)
 * by the DKIM endpoints.
 *
 * Most fields use snake_case in the API; `$gracePeriodExpiresAt` and
 * `$retiresAt` are camelCase in the v1 response body — that's a known
 * API inconsistency the SDK abstracts away. The SDK reads both wire
 * forms (canonical snake_case first, falling back to the v1 camelCase)
 * and always exposes them via the camelCase PHP properties, so a future
 * v2 fix is transparent to callers.
 *
 * Plain value object — not a `Response` subclass. Instances live inside
 * {@see DkimKeyListResponse::$keys} or
 * {@see DkimRotateResponse::$newKey}/`$rotatedKey`.
 */
final class DkimKeyInfo implements JsonSerializable
{
    /** @param list<DkimDnsRecord>|null $dkimDnsRecords */
    public function __construct(
        public readonly ?string $domain = null,
        public readonly ?string $selector = null,
        public readonly ?string $publicKey = null,
        public readonly ?string $status = null,
        public readonly ?string $algorithm = null,
        public readonly ?int $keyLength = null,
        public readonly ?array $dkimDnsRecords = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $statusModifiedAt = null,
        public readonly ?string $gracePeriodExpiresAt = null,
        public readonly ?string $retiresAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
     */
    public static function fromArray(array $data): self
    {
        $rawRecords = ResponseValidator::asNullableListOfArrays($data, 'dkim_dns_records');
        $dnsRecords = $rawRecords === null
            ? null
            : array_map(
                static fn (array $r): DkimDnsRecord => DkimDnsRecord::fromArray($r),
                $rawRecords,
            );

        return new self(
            domain: ResponseValidator::asNullableString($data, 'domain'),
            selector: ResponseValidator::asNullableString($data, 'selector'),
            publicKey: ResponseValidator::asNullableString($data, 'public_key'),
            status: ResponseValidator::asNullableString($data, 'status'),
            algorithm: ResponseValidator::asNullableString($data, 'algorithm'),
            keyLength: ResponseValidator::asNullableInt($data, 'key_length'),
            dkimDnsRecords: $dnsRecords,
            createdAt: ResponseValidator::asNullableString($data, 'created_at'),
            statusModifiedAt: ResponseValidator::asNullableString($data, 'status_modified_at'),
            gracePeriodExpiresAt: ResponseValidator::asNullableStringFromKeys(
                $data,
                ['grace_period_expires_at', 'gracePeriodExpiresAt'],
            ),
            retiresAt: ResponseValidator::asNullableStringFromKeys(
                $data,
                ['retires_at', 'retiresAt'],
            ),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->domain !== null) {
            $out['domain'] = $this->domain;
        }
        if ($this->selector !== null) {
            $out['selector'] = $this->selector;
        }
        if ($this->publicKey !== null) {
            $out['public_key'] = $this->publicKey;
        }
        if ($this->status !== null) {
            $out['status'] = $this->status;
        }
        if ($this->algorithm !== null) {
            $out['algorithm'] = $this->algorithm;
        }
        if ($this->keyLength !== null) {
            $out['key_length'] = $this->keyLength;
        }
        if ($this->dkimDnsRecords !== null) {
            $out['dkim_dns_records'] = $this->dkimDnsRecords;
        }
        if ($this->createdAt !== null) {
            $out['created_at'] = $this->createdAt;
        }
        if ($this->statusModifiedAt !== null) {
            $out['status_modified_at'] = $this->statusModifiedAt;
        }
        if ($this->gracePeriodExpiresAt !== null) {
            $out['grace_period_expires_at'] = $this->gracePeriodExpiresAt;
        }
        if ($this->retiresAt !== null) {
            $out['retires_at'] = $this->retiresAt;
        }
        return $out;
    }
}
