<?php

namespace PostSMTP\Vendor\GuzzleHttp\Handler;

use PostSMTP\Vendor\Psr\Http\Message\RequestInterface;
interface CurlFactoryInterface
{
    /**
     * Creates a cURL handle resource.
     *
     * @param RequestInterface $request Request
     * @param array            $options Transfer options
     *
     * @throws \RuntimeException when an option cannot be applied
     */
    public function create(\PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request, array $options) : \PostSMTP\Vendor\GuzzleHttp\Handler\EasyHandle;
    /**
     * Release an easy handle, allowing it to be reused or closed.
     *
     * This function must call unset on the easy handle's "handle" property.
     */
    public function release(\PostSMTP\Vendor\GuzzleHttp\Handler\EasyHandle $easy) : void;
}
