<?php

declare (strict_types=1);
namespace PostSMTP\Vendor\GuzzleHttp\Promise;

final class Is
{
    /**
     * Returns true if a promise is pending.
     */
    public static function pending(\PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface $promise) : bool
    {
        return $promise->getState() === \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled or rejected.
     */
    public static function settled(\PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface $promise) : bool
    {
        return $promise->getState() !== \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled.
     */
    public static function fulfilled(\PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface $promise) : bool
    {
        return $promise->getState() === \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface::FULFILLED;
    }
    /**
     * Returns true if a promise is rejected.
     */
    public static function rejected(\PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface $promise) : bool
    {
        return $promise->getState() === \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface::REJECTED;
    }
}
