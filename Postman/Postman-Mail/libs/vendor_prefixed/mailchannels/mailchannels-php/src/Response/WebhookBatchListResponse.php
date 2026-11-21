<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /webhook-batch`. Spec schema:
 * `WebhookBatchResult`. Wraps a list of {@see WebhookBatch} entries
 * under the `webhook_batches` JSON key.
 */
final class WebhookBatchListResponse extends Response
{
    /** @var list<WebhookBatch>|null */
    public readonly ?array $webhookBatches;

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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'webhook_batches');
        $this->webhookBatches = $rawList === null
            ? null
            : array_map(
                static fn (array $b): WebhookBatch => WebhookBatch::fromArray($b),
                $rawList,
            );
    }
}
