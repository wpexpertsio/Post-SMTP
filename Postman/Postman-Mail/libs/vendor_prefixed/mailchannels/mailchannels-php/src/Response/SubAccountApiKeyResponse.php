<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `POST /sub-account/{handle}/api-key`. The API
 * returns a single `APIKey` object directly as the response body.
 * Exposes that as `$apiKey`.
 *
 * `$apiKey->key` contains the raw key string returned by the create
 * endpoint — treat it as a credential. The list endpoint omits the
 * secret material.
 */
final class SubAccountApiKeyResponse extends Response
{
    public readonly ?SubAccountApiKey $apiKey;

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
        $this->apiKey = $body === [] ? null : SubAccountApiKey::fromArray($body);
    }
}
