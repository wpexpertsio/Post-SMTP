<?php

namespace PostSMTP\Vendor\GuzzleHttp;

use PostSMTP\Vendor\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(\PostSMTP\Vendor\Psr\Http\Message\MessageInterface $message) : ?string;
}
