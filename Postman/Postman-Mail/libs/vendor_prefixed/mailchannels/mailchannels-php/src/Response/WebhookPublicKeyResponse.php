<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /webhook/public-key`. Spec schema: `Key`.
 *
 * `$id` is the key identifier referenced by the `keyid` parameter in
 * webhook signature headers; `$key` is the public key body (typically
 * base64-encoded Ed25519) used by
 * {@see \MailChannels\Webhook\Verifier} to verify inbound signatures.
 */
final class WebhookPublicKeyResponse extends Response
{
    public readonly ?string $id;
    public readonly ?string $key;

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
        $this->id = ResponseValidator::asNullableString($body, 'id');
        $this->key = ResponseValidator::asNullableString($body, 'key');
    }
}
