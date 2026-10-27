<?php

namespace PostSMTP\Vendor\Psr\Http\Client;

use PostSMTP\Vendor\Psr\Http\Message\RequestInterface;
use PostSMTP\Vendor\Psr\Http\Message\ResponseInterface;
interface ClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(\PostSMTP\Vendor\Psr\Http\Message\RequestInterface $request) : \PostSMTP\Vendor\Psr\Http\Message\ResponseInterface;
}
