<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Webhook;

/**
 * Parsed RFC 9421 Signature-Input header parameters.
 */
final class SignatureParameters
{
    /** @param list<string> $coveredComponents */
    public function __construct(
        public readonly string $signatureName,
        public readonly array $coveredComponents,
        public readonly ?int $created = null,
        public readonly ?string $algorithm = null,
        public readonly ?string $keyId = null,
        public readonly string $raw = '',
    ) {
    }
}
