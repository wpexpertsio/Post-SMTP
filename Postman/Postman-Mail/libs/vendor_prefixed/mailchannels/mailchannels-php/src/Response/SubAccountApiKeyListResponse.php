<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /sub-account/{handle}/api-key`. The API
 * returns a bare JSON array of `APIKey` entries;
 * {@see \MailChannels\Transport} wraps bare arrays under the `data`
 * key during decoding.
 *
 * The list endpoint typically omits the secret `$key` material on
 * each entry; only `$id` is reliably populated.
 */
final class SubAccountApiKeyListResponse extends Response
{
    /** @var list<SubAccountApiKey>|null */
    public readonly ?array $apiKeys;

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
        $this->apiKeys = $rawList === null
            ? null
            : array_map(
                static fn (array $k): SubAccountApiKey => SubAccountApiKey::fromArray($k),
                $rawList,
            );
    }
}
