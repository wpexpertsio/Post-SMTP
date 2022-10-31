<?php

namespace PostSMTP\Vendor\GuzzleHttp\Promise;

final class Is
{
    /**
     * Returns true if a promise is pending.
     *
     * @return bool
     */
    public static function pending(\PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled or rejected.
     *
     * @return bool
     */
    public static function settled(\PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() !== \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled.
     *
     * @return bool
     */
    public static function fulfilled(\PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface::FULFILLED;
    }
    /**
     * Returns true if a promise is rejected.
     *
     * @return bool
     */
    public static function rejected(\PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \PostSMTP\Vendor\GuzzleHttp\Promise\PromiseInterface::REJECTED;
    }
}
