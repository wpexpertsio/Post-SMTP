<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `POST /webhook/validate`. Spec schema:
 * `WebhookValidationResults`.
 *
 * `$allPassed` is true when every enrolled webhook returned a 2xx HTTP
 * status during the validation test. `$results` carries the per-
 * webhook outcomes including the actual HTTP response MailChannels
 * received from each endpoint.
 *
 * ```php
 * $results = $client->webhooks->validate();
 * if ($results->allPassed !== true) {
 *     foreach ($results->results ?? [] as $r) {
 *         if ($r->result === 'failed') {
 *             echo $r->webhook, ' failed: HTTP ', $r->response?->status, "\n";
 *         }
 *     }
 * }
 * ```
 */
final class WebhookValidationResultsResponse extends Response
{
    public readonly ?bool $allPassed;

    /** @var list<WebhookValidationResult>|null */
    public readonly ?array $results;

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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'results');
        $this->allPassed = ResponseValidator::asNullableBool($body, 'all_passed');
        $this->results = $rawList === null
            ? null
            : array_map(
                static fn (array $r): WebhookValidationResult => WebhookValidationResult::fromArray($r),
                $rawList,
            );
    }
}
