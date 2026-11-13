<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels;

final class Version
{
    public const VERSION = '2.2.0';

    public static function userAgent(): string
    {
        return 'mailchannels-php/' . self::VERSION;
    }
}
