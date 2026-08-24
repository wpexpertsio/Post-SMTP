<?php

declare (strict_types=1);
namespace PostSMTP\Vendor\GuzzleHttp\Promise;

/**
 * Interface used with classes that return a promise.
 */
interface PromisorInterface
{
    /**
     * Returns a promise.
     */
    public function promise() : \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface;
}
