<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Enum;

enum CustomTrackingScope: string
{
    case Click = 'click';
    case Open = 'open';
    case Unsubscribe = 'unsubscribe';
}
