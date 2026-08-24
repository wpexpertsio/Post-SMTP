<?php

namespace PostSMTP\Vendor\GuzzleHttp;

use PostSMTP\Vendor\Psr\Http\Message\MessageInterface;
final class BodySummarizer implements \PostSMTP\Vendor\GuzzleHttp\BodySummarizerInterface
{
    /**
     * @var int|null
     */
    private $truncateAt;
    public function __construct(int $truncateAt = null)
    {
        $this->truncateAt = $truncateAt;
    }
    /**
     * Returns a summarized message body.
     */
    public function summarize(\PostSMTP\Vendor\Psr\Http\Message\MessageInterface $message) : ?string
    {
        return $this->truncateAt === null ? \PostSMTP\Vendor\GuzzleHttp\Psr7\Message::bodySummary($message) : \PostSMTP\Vendor\GuzzleHttp\Psr7\Message::bodySummary($message, $this->truncateAt);
    }
}
