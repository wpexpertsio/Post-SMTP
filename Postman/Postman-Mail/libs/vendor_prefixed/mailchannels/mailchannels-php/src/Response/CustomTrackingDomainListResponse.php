<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response returned by
 * {@see \MailChannels\Resource\CustomTrackingDomains::list()}. Spec schema:
 * `CustomTrackingDomainListResponse`.
 *
 * ```php
 * $list = $client->customTrackingDomains->list(scope: CustomTrackingScope::Click);
 * foreach ($list->customTrackingDomains ?? [] as $domain) {
 *     echo $domain->hostname, ' (', $domain->status, ")\n";
 * }
 * ```
 */
final class CustomTrackingDomainListResponse extends Response
{
    /** @var list<CustomTrackingDomain>|null */
    public readonly ?array $customTrackingDomains;

    public readonly ?int $total;

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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'custom_tracking_domains');
        $this->customTrackingDomains = $rawList === null
            ? null
            : array_map(
                static fn (array $d): CustomTrackingDomain => CustomTrackingDomain::fromArray($d),
                $rawList,
            );
        $this->total = ResponseValidator::asNullableInt($body, 'total');
    }
}
