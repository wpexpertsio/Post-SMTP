<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Dkim::list()}.
 * Wraps a list of typed {@see DkimKeyInfo} entries.
 *
 * ```php
 * $list = $client->dkim->list('example.com', includeDnsRecord: true);
 * foreach ($list->keys ?? [] as $key) {
 *     echo $key->selector, ' (', $key->status, ")\n";
 *     foreach ($key->dkimDnsRecords ?? [] as $record) {
 *         echo "  publish ", $record->name, ' ', $record->type, ' ', $record->value, "\n";
 *     }
 * }
 * ```
 */
final class DkimKeyListResponse extends Response
{
    /** @var list<DkimKeyInfo>|null */
    public readonly ?array $keys;

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'keys');
        $this->keys = $rawList === null
            ? null
            : array_map(
                static fn (array $k): DkimKeyInfo => DkimKeyInfo::fromArray($k),
                $rawList,
            );
    }
}
