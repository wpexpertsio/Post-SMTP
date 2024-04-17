<?php

declare (strict_types=1);
namespace PostSMTP\Vendor\GuzzleHttp\Promise;

/**
 * Exception thrown when too many errors occur in the some() or any() methods.
 */
class AggregateException extends \PostSMTP\Vendor\GuzzleHttp\Promise\RejectionException
{
    public function __construct(string $msg, array $reasons)
    {
        parent::__construct($reasons, \sprintf('%s; %d rejected promises', $msg, \count($reasons)));
    }
}
