<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `POST /sub-account/{handle}/smtp-password`. The
 * API returns a single `SMTPPassword` object directly as the response
 * body. Exposes that as `$smtpPassword`.
 *
 * `$smtpPassword->smtpPassword` is the raw credential returned by the
 * create endpoint — treat as a secret.
 */
final class SubAccountSmtpPasswordResponse extends Response
{
    public readonly ?SubAccountSmtpPassword $smtpPassword;

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
        $this->smtpPassword = $body === [] ? null : SubAccountSmtpPassword::fromArray($body);
    }
}
