<?php

namespace PostSMTP\Vendor\GuzzleHttp\Tests\Handler;

/**
 * A credential whose stringification always fails.
 */
final class ThrowingStringableCredential
{
    public function __toString() : string
    {
        throw new \RuntimeException('credential unavailable');
    }
}
