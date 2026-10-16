<?php

namespace PostSMTP\Vendor\GuzzleHttp\Tests;

use PostSMTP\Vendor\GuzzleHttp\Client;
use PostSMTP\Vendor\Http\Client\Tests\HttpClientTest;
use PostSMTP\Vendor\Psr\Http\Client\ClientInterface;
/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HttplugIntegrationTest extends HttpClientTest
{
    protected function createHttpAdapter() : ClientInterface
    {
        return new Client();
    }
}
