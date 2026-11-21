<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Enum;

enum MetricsSortOrder: string
{
    case Ascending = 'asc';
    case Descending = 'desc';
}
