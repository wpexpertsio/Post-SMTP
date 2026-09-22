<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Enum;

/**
 * The type of mail a suppression entry blocks. Each entry in the
 * `suppression_entries` array of POST /suppression-list carries a list of
 * these values.
 */
enum SuppressionType: string
{
    case Transactional = 'transactional';
    case NonTransactional = 'non-transactional';
}
