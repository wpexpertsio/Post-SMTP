<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /metrics/engagement`. Spec schema:
 * `MetricsEngagement`.
 *
 * Exposes top-level open/click counts plus their time-series breakdowns
 * under the `*Buckets` properties.
 *
 * `unique*` fields count distinct messages rather than events — a message
 * opened five times still counts once toward `$uniqueOpen`, unlike `$open`.
 * Divide `$uniqueOpen` by `$uniqueOpenTrackingDelivered` (and the click
 * equivalents) to compute open/click rates that can't exceed 100%.
 */
final class MetricsEngagementResponse extends Response
{
    public readonly ?string $startTime;
    public readonly ?string $endTime;
    public readonly ?int $open;
    public readonly ?int $click;
    public readonly ?int $openTrackingDelivered;
    public readonly ?int $clickTrackingDelivered;
    public readonly ?int $uniqueOpen;
    public readonly ?int $uniqueClick;
    public readonly ?int $uniqueOpenTrackingDelivered;
    public readonly ?int $uniqueClickTrackingDelivered;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $openBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $clickBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $openTrackingDeliveredBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $clickTrackingDeliveredBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $uniqueOpenBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $uniqueClickBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $uniqueOpenTrackingDeliveredBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $uniqueClickTrackingDeliveredBuckets;

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
        $this->open = ResponseValidator::asNullableInt($body, 'open');
        $this->click = ResponseValidator::asNullableInt($body, 'click');
        $this->openTrackingDelivered = ResponseValidator::asNullableInt($body, 'open_tracking_delivered');
        $this->clickTrackingDelivered = ResponseValidator::asNullableInt($body, 'click_tracking_delivered');
        $this->uniqueOpen = ResponseValidator::asNullableInt($body, 'unique_open');
        $this->uniqueClick = ResponseValidator::asNullableInt($body, 'unique_click');
        $this->uniqueOpenTrackingDelivered = ResponseValidator::asNullableInt($body, 'unique_open_tracking_delivered');
        $this->uniqueClickTrackingDelivered = ResponseValidator::asNullableInt($body, 'unique_click_tracking_delivered');
        $this->openBuckets = MetricsBucket::listFromParent($buckets, 'open');
        $this->clickBuckets = MetricsBucket::listFromParent($buckets, 'click');
        $this->openTrackingDeliveredBuckets = MetricsBucket::listFromParent($buckets, 'open_tracking_delivered');
        $this->clickTrackingDeliveredBuckets = MetricsBucket::listFromParent($buckets, 'click_tracking_delivered');
        $this->uniqueOpenBuckets = MetricsBucket::listFromParent($buckets, 'unique_open');
        $this->uniqueClickBuckets = MetricsBucket::listFromParent($buckets, 'unique_click');
        $this->uniqueOpenTrackingDeliveredBuckets = MetricsBucket::listFromParent($buckets, 'unique_open_tracking_delivered');
        $this->uniqueClickTrackingDeliveredBuckets = MetricsBucket::listFromParent($buckets, 'unique_click_tracking_delivered');
    }
}
