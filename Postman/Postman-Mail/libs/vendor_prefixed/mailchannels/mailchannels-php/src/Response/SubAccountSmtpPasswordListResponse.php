<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /sub-account/{handle}/smtp-password`. The
 * API returns a bare JSON array of `SMTPPassword` entries;
 * {@see \MailChannels\Transport} wraps bare arrays under the `data`
 * key during decoding.
 *
 * The list endpoint typically omits the raw `$smtpPassword` material
 * on each entry; only `$id` and `$enabled` are reliably populated.
 */
final class SubAccountSmtpPasswordListResponse extends Response
{
    /** @var list<SubAccountSmtpPassword>|null */
    public readonly ?array $smtpPasswords;

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type.
     */
    public function __construct(Response $response)
    {
        $body = $response->data;
        parent::__construct(
            $response->data,
            $response->statusCode,
            $response->headers,
            $response->rawBody,
        );
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'data');
        $this->smtpPasswords = $rawList === null
            ? null
            : array_map(
                static fn (array $p): SubAccountSmtpPassword => SubAccountSmtpPassword::fromArray($p),
                $rawList,
            );
    }
}
