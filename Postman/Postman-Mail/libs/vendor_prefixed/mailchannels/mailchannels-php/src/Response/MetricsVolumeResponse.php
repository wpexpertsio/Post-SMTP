<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /metrics/volume`. Spec schema:
 * `MetricsVolume`.
 *
 * Exposes top-level processed/delivered/dropped counts plus their
 * time-series breakdowns under the `*Buckets` properties.
 */
final class MetricsVolumeResponse extends Response
{
    public readonly ?string $startTime;
    public readonly ?string $endTime;
    public readonly ?int $processed;
    public readonly ?int $delivered;
    public readonly ?int $dropped;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $processedBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $deliveredBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $droppedBuckets;

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
        $this->processed = ResponseValidator::asNullableInt($body, 'processed');
        $this->delivered = ResponseValidator::asNullableInt($body, 'delivered');
        $this->dropped = ResponseValidator::asNullableInt($body, 'dropped');
        $this->processedBuckets = MetricsBucket::listFromParent($buckets, 'processed');
        $this->deliveredBuckets = MetricsBucket::listFromParent($buckets, 'delivered');
        $this->droppedBuckets = MetricsBucket::listFromParent($buckets, 'dropped');
    }
}
