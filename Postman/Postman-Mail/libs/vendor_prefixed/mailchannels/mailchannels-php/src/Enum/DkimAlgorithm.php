<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Enum;

enum DkimAlgorithm: string
{
    case Rsa = 'rsa';
}
