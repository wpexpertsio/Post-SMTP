<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `POST /sub-account` (create). The API returns a
 * single `SubAccountDetails` object directly as the response body (no
 * wrapper key). Exposes that as `$subAccount` for typed access.
 */
final class SubAccountResponse extends Response
{
    public readonly ?SubAccountDetails $subAccount;

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
        $this->subAccount = $body === [] ? null : SubAccountDetails::fromArray($body);
    }
}
