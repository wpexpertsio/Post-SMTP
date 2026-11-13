<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Dkim::create()}
 * for `POST /domains/{domain}/dkim-keys`.
 *
 * The API returns the created `DKIMKeyInfo` object directly as the
 * response body (no wrapper key in the JSON). This class exposes that
 * info as `$key` for ergonomic typed access while keeping the body
 * itself array-accessible via the inherited {@see Response} surface.
 *
 * ```php
 * $created = $client->dkim->create('example.com', 'mc1', algorithm: DkimAlgorithm::Rsa);
 * echo $created->key->selector;
 * foreach ($created->key->dkimDnsRecords ?? [] as $record) {
 *     echo "publish ", $record->name, ' ', $record->type, ' ', $record->value, "\n";
 * }
 * ```
 */
final class DkimKeyResponse extends Response
{
    public readonly ?DkimKeyInfo $key;

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type. An empty body leaves `$key` null.
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
        $this->key = $body === [] ? null : DkimKeyInfo::fromArray($body);
    }
}
