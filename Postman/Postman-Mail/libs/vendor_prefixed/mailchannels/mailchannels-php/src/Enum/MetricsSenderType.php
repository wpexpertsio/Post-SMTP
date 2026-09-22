<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Enum;

enum MetricsSenderType: string
{
    case Campaigns = 'campaigns';
    case SubAccounts = 'sub-accounts';
}
