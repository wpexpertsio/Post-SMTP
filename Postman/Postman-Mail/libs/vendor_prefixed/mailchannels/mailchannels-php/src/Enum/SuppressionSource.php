<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Enum;

enum SuppressionSource: string
{
    case Api = 'api';
    case UnsubscribeLink = 'unsubscribe_link';
    case ListUnsubscribe = 'list_unsubscribe';
    case HardBounce = 'hard_bounce';
    case SpamComplaint = 'spam_complaint';
}
