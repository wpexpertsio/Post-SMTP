<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /metrics/recipient-behaviour`. Spec schema:
 * `MetricsRecipientBehaviour`.
 *
 * Exposes top-level unsubscribe counts plus their time-series
 * breakdowns under the `*Buckets` properties.
 */
final class MetricsRecipientBehaviourResponse extends Response
{
    public readonly ?string $startTime;
    public readonly ?string $endTime;
    public readonly ?int $unsubscribed;
    public readonly ?int $unsubscribeDelivered;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $unsubscribedBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $unsubscribeDeliveredBuckets;

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
        $buckets = ResponseValidator::asNullableAssocArray($body, 'buckets');
        $this->startTime = ResponseValidator::asNullableString($body, 'start_time');
        $this->endTime = ResponseValidator::asNullableString($body, 'end_time');
        $this->unsubscribed = ResponseValidator::asNullableInt($body, 'unsubscribed');
        $this->unsubscribeDelivered = ResponseValidator::asNullableInt($body, 'unsubscribe_delivered');
        $this->unsubscribedBuckets = MetricsBucket::listFromParent($buckets, 'unsubscribed');
        $this->unsubscribeDeliveredBuckets = MetricsBucket::listFromParent($buckets, 'unsubscribe_delivered');
    }
}
