<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /webhook`. The API returns a bare JSON array
 * of {@see WebhookSubscription} entries (no envelope); the SDK's
 * {@see \MailChannels\Transport} wraps bare arrays under the `data`
 * key in the decoded body, and this class extracts the typed list from
 * there.
 *
 * ```php
 * foreach ($client->webhooks->list()->webhooks ?? [] as $sub) {
 *     echo $sub->webhook, "\n";
 * }
 * ```
 */
final class WebhookListResponse extends Response
{
    /** @var list<WebhookSubscription>|null */
    public readonly ?array $webhooks;

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
        // Transport wraps bare-array responses as ['data' => [...]].
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'data');
        $this->webhooks = $rawList === null
            ? null
            : array_map(
                static fn (array $s): WebhookSubscription => WebhookSubscription::fromArray($s),
                $rawList,
            );
    }
}
