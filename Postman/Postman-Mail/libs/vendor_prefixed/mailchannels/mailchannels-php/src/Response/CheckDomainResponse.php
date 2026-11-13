<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\CheckDomain::check()}.
 *
 * The `/check-domain` endpoint returns deeply nested verdict data — DKIM
 * results (one per selector), SPF status, Domain Lockdown verdict, and
 * sender-domain DNS (A/MX) checks. The SDK exposes the top-level grouping
 * as `$checkResults` (an associative array preserving the API's shape)
 * and `$references` (documentation URLs the API points at). Drill into
 * the verdicts via array access:
 *
 * ```php
 * $r = $client->checkDomain->check('example.com');
 * echo $r->checkResults['spf']['verdict'];                // 'passed' | 'failed'
 * echo $r->checkResults['domain_lockdown']['verdict'];
 * foreach ($r->checkResults['dkim'] ?? [] as $dkim) {
 *     echo $dkim['dkim_selector'], ': ', $dkim['verdict'], "\n";
 * }
 * ```
 *
 * Both fields are nullable. The nested shapes are not further typed at
 * this layer; callers that want stronger typing should construct their
 * own value objects from the arrays.
 */
final class CheckDomainResponse extends Response
{
    /** @var array<string, mixed>|null */
    public readonly ?array $checkResults;

    /** @var list<string>|null */
    public readonly ?array $references;

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
        $this->checkResults = ResponseValidator::asNullableAssocArray($body, 'check_results');
        $this->references = ResponseValidator::asNullableListOfStrings($body, 'references');
    }
}
