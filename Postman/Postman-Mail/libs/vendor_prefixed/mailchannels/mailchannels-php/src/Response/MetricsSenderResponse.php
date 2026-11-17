<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response for `GET /metrics/senders/{sender_type}`. Spec schema:
 * `MetricsSenderResponse`.
 *
 * Differs from the time-series metrics endpoints: this one is a
 * paginated list of per-sender rollups (`$senders`), not a bucketed
 * time series. `$limit`/`$offset`/`$total` describe the pagination
 * window.
 */
final class MetricsSenderResponse extends Response
{
    public readonly ?string $startTime;
    public readonly ?string $endTime;
    public readonly ?int $limit;
    public readonly ?int $offset;
    public readonly ?int $total;

    /** @var list<MetricsSender>|null */
    public readonly ?array $senders;

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
        $rawSenders = ResponseValidator::asNullableListOfArrays($body, 'senders');
        $this->startTime = ResponseValidator::asNullableString($body, 'start_time');
        $this->endTime = ResponseValidator::asNullableString($body, 'end_time');
        $this->limit = ResponseValidator::asNullableInt($body, 'limit');
        $this->offset = ResponseValidator::asNullableInt($body, 'offset');
        $this->total = ResponseValidator::asNullableInt($body, 'total');
        $this->senders = $rawSenders === null
            ? null
            : array_map(
                static fn (array $s): MetricsSender => MetricsSender::fromArray($s),
                $rawSenders,
            );
    }
}
