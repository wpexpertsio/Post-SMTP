<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use DateTimeInterface;
use PostSMTP\Vendor\MailChannels\Enum\MetricsInterval;
use PostSMTP\Vendor\MailChannels\Enum\MetricsSenderType;
use PostSMTP\Vendor\MailChannels\Enum\MetricsSortOrder;
use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\MetricsEngagementResponse;
use PostSMTP\Vendor\MailChannels\Response\MetricsPerformanceResponse;
use PostSMTP\Vendor\MailChannels\Response\MetricsRecipientBehaviourResponse;
use PostSMTP\Vendor\MailChannels\Response\MetricsSenderResponse;
use PostSMTP\Vendor\MailChannels\Response\MetricsVolumeResponse;

final class Metrics extends AbstractResource
{
    public function engagement(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): MetricsEngagementResponse {
        return new MetricsEngagementResponse(
            $this->timeSeries('/metrics/engagement', $startTime, $endTime, $campaignId, $interval),
        );
    }

    public function performance(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): MetricsPerformanceResponse {
        return new MetricsPerformanceResponse(
            $this->timeSeries('/metrics/performance', $startTime, $endTime, $campaignId, $interval),
        );
    }

    public function recipientBehaviour(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): MetricsRecipientBehaviourResponse {
        return new MetricsRecipientBehaviourResponse(
            $this->timeSeries('/metrics/recipient-behaviour', $startTime, $endTime, $campaignId, $interval),
        );
    }

    public function recipientBehavior(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): MetricsRecipientBehaviourResponse {
        return $this->recipientBehaviour($startTime, $endTime, $campaignId, $interval);
    }

    public function volume(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): MetricsVolumeResponse {
        return new MetricsVolumeResponse(
            $this->timeSeries('/metrics/volume', $startTime, $endTime, $campaignId, $interval),
        );
    }

    public function senders(
        MetricsSenderType|string $senderType,
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?int $limit = null,
        ?int $offset = null,
        MetricsSortOrder|string|null $sortOrder = null,
    ): MetricsSenderResponse {
        $type = $senderType instanceof MetricsSenderType ? $senderType->value : $senderType;
        $query = self::compactOrNull([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'limit' => $limit,
            'offset' => $offset,
            'sort_order' => $sortOrder,
        ]);
        $path = self::encodedPath('metrics', 'senders', $type);
        return new MetricsSenderResponse(
            $this->request('GET', $path, null, $query),
        );
    }

    private function timeSeries(
        string $path,
        DateTimeInterface|string|null $startTime,
        DateTimeInterface|string|null $endTime,
        ?string $campaignId,
        MetricsInterval|string|null $interval,
    ): Response {
        $query = self::compactOrNull([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'campaign_id' => $campaignId,
            'interval' => $interval,
        ]);
        return $this->request('GET', $path, null, $query);
    }
}
