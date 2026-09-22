<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /sub-account`. The API returns a bare JSON
 * array of `SubAccountDetails` entries; {@see \MailChannels\Transport}
 * wraps bare arrays under the `data` key during decoding, and this
 * class extracts the typed list from there.
 */
final class SubAccountListResponse extends Response
{
    /** @var list<SubAccountDetails>|null */
    public readonly ?array $subAccounts;

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
        $this->subAccounts = $rawList === null
            ? null
            : array_map(
                static fn (array $s): SubAccountDetails => SubAccountDetails::fromArray($s),
                $rawList,
            );
    }
}
