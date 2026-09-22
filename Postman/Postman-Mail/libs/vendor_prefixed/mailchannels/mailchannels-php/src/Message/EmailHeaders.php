<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Message;

use PostSMTP\Vendor\MailChannels\Exception\ValidationException;

/**
 * Helper guarding against custom headers that the MailChannels API controls
 * via structured fields. Mirrors the reserved-headers list in the Python SDK.
 */
final class EmailHeaders
{
    /**
     * Header names the MailChannels Email API constructs internally from
     * structured payload fields, sets during outbound processing, or refuses
     * to forward. Custom headers carrying any of these names are rejected at
     * SDK boundaries rather than being silently dropped or doubled by the
     * server.
     */
    private const RESERVED = [
        'authentication-results',
        'bcc',
        'cc',
        'content-transfer-encoding',
        'content-type',
        'dkim-signature',
        'from',
        'message-id',
        'received',
        'reply-to',
        'subject',
        'to',
    ];

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    public static function validate(array $headers): array
    {
        $bad = [];
        foreach ($headers as $name => $_value) {
            if (in_array(strtolower((string) $name), self::RESERVED, true)) {
                $bad[] = (string) $name;
            }
        }
        if ($bad !== []) {
            sort($bad);
            throw new ValidationException(
                'Custom headers cannot include reserved message headers: ' . implode(', ', $bad),
                errorCode: 'ReservedHeader',
            );
        }
        return $headers;
    }
}
