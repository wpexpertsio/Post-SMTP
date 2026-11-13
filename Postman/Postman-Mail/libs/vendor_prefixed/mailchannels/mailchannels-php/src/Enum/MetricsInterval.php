<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Enum;

enum MetricsInterval: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
}
